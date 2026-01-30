<?php

declare(strict_types=1);

namespace Sura\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sura\Support\Router;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        // Очищаем статические свойства перед каждым тестом
        $reflection = new \ReflectionClass(Router::class);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_STATIC) as $property) {
            if ($property->getName() !== 'placeholders') { // сохраняем placeholders
                $property->setValue(null, match ($property->getType()?->getName()) {
                    'array' => [],
                    'string' => '',
                    'null', 'mixed' => null,
                    default => null,
                });
            }
        }

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testFromGlobalsCreatesRouterInstance(): void
    {
        $router = Router::fromGlobals();

        $this->assertInstanceOf(Router::class, $router);
        $this->assertEquals('/test', Router::getRequestUri());
        $this->assertEquals('GET', Router::getRequestMethod());
    }

    public function testFromGlobalsThrowsErrorWhenUriNotAvailable(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $_SERVER['HTTP_HOST'] = '';

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('err');

        Router::fromGlobals();
    }

    public function testAddRegistersRoutes(): void
    {
        $routes = [
            '/' => 'Home@index',
            '/user/:num' => 'User@show',
        ];

        $router = new Router('/test');
        $router->add($routes);

        $this->assertEquals($routes, Router::getRoutes());
    }

    public function testIsFoundFindsExactMatch(): void
    {
        $router = new Router('/exact');
        $router->add(['/exact' => 'Exact@handler']);

        $result = $router->isFound();

        $this->assertTrue($result);
        $this->assertEquals('Exact@handler', Router::getRequestHandler());
        $this->assertEmpty(Router::getParams());
    }

    public function testIsFoundCachesResult(): void
    {
        $router = new Router('/cached');
        $router->add(['/cached' => 'Cached@run']);

        // Первый вызов — должен найти и закэшировать
        $this->assertTrue($router->isFound());

        // Изменим маршруты — кэш всё ещё должен вернуть старое значение
        Router::$routes = [];
        $this->assertTrue($router->isFound()); // Берётся из кэша
        $this->assertEquals('Cached@run', Router::getRequestHandler());
    }

    public function testIsFoundMatchesPlaceholderRoute(): void
    {
        $router = new Router('/user/123');
        $router->add(['/user/:num' => 'User@show']);

        $result = $router->isFound();

        $this->assertTrue($result);
        $this->assertEquals('User@show', Router::getRequestHandler());
        $this->assertEquals(['123'], Router::getParams());
    }

    public function testIsFoundMatchesMultiplePlaceholders(): void
    {
        $router = new Router('/post/456/comment/789');
        $router->add(['/post/:num/comment/:num' => 'Comment@show']);

        $result = $router->isFound();

        $this->assertTrue($result);
        $this->assertEquals(['456', '789'], Router::getParams());
    }

    public function testIsFoundReturnsFalseWhenNoMatch(): void
    {
        $router = new Router('/not-found');
        $router->add(['/home' => 'Home@index']);

        $result = $router->isFound();

        $this->assertFalse($result);
        $this->assertNull(Router::getRequestHandler());
    }

    public function testExecuteHandlerRunsCallable(): void
    {
        $router = new Router('/test');

        $callback = function ($name) {
            return "Hello, $name";
        };

        $result = $router->executeHandler($callback, ['Alice']);

        $this->assertEquals('Hello, Alice', $result);
    }

    public function testExecuteHandlerSupportsClosureWithParams(): void
    {
        $router = new Router('/test');

        $callback = fn($id, $type) => "ID: $id, Type: $type";

        $result = $router->executeHandler($callback, ['123', 'article']);

        $this->assertEquals('ID: 123, Type: article', $result);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExecuteHandlerInstantiatesControllerAndCallsAction(): void
    {
        // Создаём временный контроллер в runtime
        eval('
            namespace Mozg\modules;
            class TestController {
                public function handle($params) {
                    return "Called TestController@handle with " . json_encode($params);
                }
            }
        ');

        $router = new Router('/test');
        $result = $router->executeHandler('TestController@handle', [['id' => 1]]);

        $this->assertEquals('Called TestController@handle with {"id":1,"params":""}', $result);
        $this->assertEquals('TestController', Router::getControllerName());
        $this->assertEquals('handle', Router::getActionName());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExecuteHandlerThrowsErrorIfControllerNotFound(): void
    {
        $router = new Router('/test');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Class 'UnknownController' not found");

        $router->executeHandler('UnknownController@action');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExecuteHandlerThrowsErrorIfMethodNotFound(): void
    {
        eval('
            namespace Mozg\modules;
            class NoMethodController {}
        ');

        $router = new Router('/test');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Method '\\App\\Modules\\NoMethodController::missing()' not found");

        $router->executeHandler('NoMethodController@missing');
    }

    public function testExecuteHandlerThrowsErrorOnInvalidHandler(): void
    {
        $router = new Router('/test');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Execute handler error');

        $router->executeHandler('invalid_handler_format');
    }
}