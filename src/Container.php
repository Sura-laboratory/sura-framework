<?php
namespace Sura;

use Psr\Container\ContainerInterface;
use Sura\Exceptions\NotFoundException;
use Sura\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Throwable;

class Container implements ContainerInterface
{
    private static ?self $instance = null;
    protected array $definitions = []; // id => ['concrete' => mixed, 'shared' => bool] - Определения сервисов
    protected array $instances = [];   // id => object - Экземпляры singleton-сервисов
    protected array $aliases = [];     // alias => id - Псевдонимы для сервисов
    protected array $resolving = [];   // stack - Стек для отслеживания циклических зависимостей

    /**
     * Получить единственный экземпляр контейнера (паттерн Singleton)
     *
     * @return self Экземпляр контейнера
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Привязать сервис к контейнеру
     *
     * Регистрирует сервис с указанным идентификатором и реализацией
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $concrete Имя класса, фабричная функция или экземпляр
     * @param bool $shared Является ли сервис одиночкой (singleton)
     */
    public function bind(string $id, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }
        $this->definitions[$id] = ['concrete' => $concrete, 'shared' => $shared];
        unset($this->instances[$id]);
    }

    /**
     * Привязать сервис-одиночку к контейнеру
     *
     * Сервис будет создан только один раз и будет использоваться повторно
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $concrete Имя класса, фабричная функция или экземпляр
     */
    public function singleton(string $id, $concrete = null): void
    {
        $this->bind($id, $concrete, true);
    }

    /**
     * Зарегистрировать существующий экземпляр в контейнере
     *
     * Помещает готовый экземпляр объекта в контейнер под указанным идентификатором
     *
     * @param string $id Идентификатор сервиса
     * @param object $object Экземпляр объекта для регистрации
     */
    public function instance(string $id, $object): void
    {
        $this->instances[$id] = $object;
    }

    /**
     * Создать псевдоним для сервиса
     *
     * Позволяет обращаться к сервису по альтернативному имени
     *
     * @param string $alias Имя псевдонима
     * @param string $id Идентификатор оригинального сервиса
     */
    public function alias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }

    /**
     * Проверить наличие сервиса в контейнере
     *
     * Проверяет, существует ли сервис по указанному идентификатору
     *
     * @param string $id Идентификатор сервиса
     * @return bool true, если сервис существует, иначе false
     */
    public function has($id): bool
    {
        $id = $this->resolveAlias($id);
        return isset($this->instances[$id]) || isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * Получить сервис из контейнера
     *
     * Возвращает экземпляр сервиса по его идентификатору
     * Если сервис не найден, выбрасывается исключение
     *
     * @param string $id Идентификатор сервиса
     * @return mixed Экземпляр сервиса
     * @throws NotFoundException если сервис не найден
     */
    public function get($id)
    {
        $id = $this->resolveAlias($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $def = $this->definitions[$id];
            $obj = $this->build($def['concrete']);
            if ($def['shared']) {
                $this->instances[$id] = $obj;
            }
            return $obj;
        }

        if (class_exists($id)) {
            $obj = $this->build($id);
            return $obj;
        }

        throw new NotFoundException("Service '{$id}' not found.");
    }

    /**
     * Создать/разрешить сервис с возможностью переопределения параметров
     *
     * Аналогичен get(), но позволяет переопределять параметры конструктора
     * Поддерживает именованные и позиционные параметры
     *
     * @param string $id Идентификатор класса или сервиса
     * @param array $params Параметры в формате имя => значение
     * @return object Экземпляр сервиса
     * @throws NotFoundException если сервис не найден
     */
    public function make(string $id, array $params = [])
    {
        $id = $this->resolveAlias($id);

        // If we already have instance and no overrides -> return it
        if (isset($this->instances[$id]) && empty($params)) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $def = $this->definitions[$id];
            // if concrete is callable and user passed params -> call wrapper that injects params
            if (is_callable($def['concrete'])) {
                $factory = $def['concrete'];
                $object = $factory($this, $params);
            } else {
                $object = $this->build($def['concrete'], $params);
            }
            if ($def['shared']) {
                $this->instances[$id] = $object;
            }
            return $object;
        }

        if (class_exists($id)) {
            return $this->build($id, $params);
        }

        throw new NotFoundException("Service '{$id}' not found for make().");
    }

    /**
     * Создать сервис из конкретного определения
     *
     * Основной метод построения экземпляров сервисов
     * Обрабатывает классы, фабрики и готовые экземпляры
     *
     * @param mixed $concrete Имя класса, фабричная функция или экземпляр
     * @param array $overrides Переопределения параметров
     * @return object Созданный экземпляр сервиса
     * @throws ContainerException При ошибках построения сервиса
     */
    protected function build($concrete, array $overrides = [])
    {
        if (is_object($concrete) && !is_callable($concrete)) {
            return $concrete;
        }

        if (is_callable($concrete)) {
            try {
                $r = new ReflectionFunction($concrete);
                // If factory accepts container and optional params, call accordingly
                $args = [];
                $params = $r->getParameters();
                foreach ($params as $p) {
                    $name = $p->getName();
                    $type = $p->getType();
                    if ($type && ! $type->isBuiltin() && $this->has($type->getName())) {
                        $args[] = $this->get($type->getName());
                        continue;
                    }
                    // pass container if requested
                    if ($type && $type->getName() === self::class) {
                        $args[] = $this;
                        continue;
                    }
                    // if override exists by name, pass it
                    if (array_key_exists($name, $overrides)) {
                        $args[] = $overrides[$name];
                        continue;
                    }
                    if ($p->isDefaultValueAvailable()) {
                        $args[] = $p->getDefaultValue();
                        continue;
                    }
                    // no way to satisfy param; give null
                    $args[] = null;
                }
                return $concrete(...$args);
            } catch (Throwable $e) {
                throw new ContainerException('Factory threw exception: ' . $e->getMessage(), 0, $e);
            }
        }

        if (is_string($concrete) && class_exists($concrete)) {
            return $this->buildClass($concrete, $overrides);
        }

        throw new ContainerException('Unable to build service.');
    }

    /**
     * Создать экземпляр класса с разрешением зависимостей
     *
     * Использует рефлексию для автоматического внедрения зависимостей
     * Проверяет циклические зависимости и вызывает конструктор
     *
     * @param string $class Имя класса
     * @param array $overrides Переопределения параметров конструктора
     * @return object Созданный экземпляр класса
     * @throws ContainerException При ошибках создания экземпляра
     */
    protected function buildClass(string $class, array $overrides = []): object
    {
        if (in_array($class, $this->resolving, true)) {
            $path = implode(' -> ', array_merge($this->resolving, [$class]));
            throw new ContainerException("Circular dependency detected: {$path}");
        }

        $this->resolving[] = $class;

        try {
            $ref = new ReflectionClass($class);
            if (! $ref->isInstantiable()) {
                throw new ContainerException("Class {$class} is not instantiable.");
            }

            $constructor = $ref->getConstructor();
            if ($constructor === null) {
                array_pop($this->resolving);
                $obj = $ref->newInstance();
                return $obj;
            }

            $params = $constructor->getParameters();
            $args = $this->resolveParameters($params, $overrides);

            $obj = $ref->newInstanceArgs($args);
            array_pop($this->resolving);
            return $obj;
        } catch (Throwable $e) {
            array_pop($this->resolving);
            throw new ContainerException("Failed building {$class}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Разрешить параметры конструктора
     *
     * Определяет значения для параметров конструктора класса
     * Поддерживает внедрение сервисов, переопределения и значения по умолчанию
     *
     * @param ReflectionParameter[] $params Параметры конструктора
     * @param array $overrides Переопределения значений параметров
     * @return array Массив значений параметров в правильном порядке
     * @throws ContainerException|NotFoundException При ошибках разрешения
     */
    protected function resolveParameters(array $params, array $overrides = []): array
    {
        $resolved = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $overrides)) {
                $resolved[] = $overrides[$name];
                continue;
            }

            // numeric override support: param position
            // (not implemented here; could be added if needed)

            if ($type && ! $type->isBuiltin()) {
                $typeName = $type->getName();
                if ($this->has($typeName)) {
                    $resolved[] = $this->get($typeName);
                    continue;
                }
                if (class_exists($typeName)) {
                    $resolved[] = $this->build($typeName);
                    continue;
                }
                if ($param->isDefaultValueAvailable()) {
                    $resolved[] = $param->getDefaultValue();
                    continue;
                }
                throw new ContainerException("Unable to resolve parameter \${$name} (type {$typeName}).");
            }

            if ($param->isDefaultValueAvailable()) {
                $resolved[] = $param->getDefaultValue();
                continue;
            }

            throw new ContainerException("Unable to resolve parameter \${$name}: no type and no default.");
        }
        return $resolved;
    }

    /**
     * Разрешить псевдоним в реальный идентификатор сервиса
     *
     * Преобразует псевдоним в оригинальный идентификатор сервиса
     * Если псевдоним не найден, возвращает исходный идентификатор
     *
     * @param string $id Идентификатор (возможно, псевдоним)
     * @return string Реальный идентификатор сервиса
     */
    protected function resolveAlias(string $id): string
    {
        return $this->aliases[$id] ?? $id;
    }

    /**
     * Расширить существующую привязку (декоратор)
     *
     * Позволяет модифицировать существующий сервис, оборачивая его
     * в дополнительную логику
     *
     * @param string $id Идентификатор сервиса для расширения
     * @param callable $callable Функция-декоратор
     * @throws NotFoundException если сервис не найден
     */
    public function extend(string $id, callable $callable): void
    {
        $id = $this->resolveAlias($id);
        if (!isset($this->definitions[$id]) && !isset($this->instances[$id]) && !class_exists($id)) {
            throw new NotFoundException("Cannot extend unknown service {$id}.");
        }

        $prev = $this->definitions[$id]['concrete'] ?? $this->instances[$id] ?? $id;

        $this->definitions[$id] = [
            'concrete' => function ($c) use ($callable, $prev) {
                $previous = is_string($prev) && $c->has($prev) ? $c->get($prev) : $prev;
                return $callable($previous, $c);
            },
            'shared' => $this->definitions[$id]['shared'] ?? false,
        ];

        unset($this->instances[$id]);
    }
}