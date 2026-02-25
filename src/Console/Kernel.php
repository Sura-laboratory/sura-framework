<?php

namespace Sura\Console;

use Sura\Container;

use Sura\Console\Commands\Command;

/**
 * Ядро консольного приложения
 */
class Kernel
{
    /**
     * Массив команд
     * 
     * @var array
     */
    protected $commands = [];


    protected Container $container;
    protected array $middleware = [];

    /**
     * @param Container $container
     * @param array $middleware Array of middleware class names to apply
     */
    public function __construct(Container $container, array $middleware = [])
    {
        $this->container = $container;
        $this->middleware = $middleware;
    }

    /**
     * Обработка аргументов командной строки
     * 
     * @param array $argv Аргументы командной строки
     * @return void
     */
    public function handle(array $argv)
    {
        // Удаляем имя скрипта из аргументов
        array_shift($argv);

        // Получаем команду
        $command = $argv[0] ?? 'list';

        // Выполняем команду
        $this->runCommand($command, array_slice($argv, 1));
    }

    /**
     * Выполнение команды
     * 
     * @param string $commandName Имя команды
     * @param array $arguments Аргументы команды
     * @return void
     */
    protected function runCommand(string $commandName, array $arguments)
    {
        // Регистрируем команды
        $this->registerCommands();

        if (isset($this->commands[$commandName])) {
            $command = new $this->commands[$commandName]();
            $command->execute($arguments);
        } else {
            echo "Команда '$commandName' не найдена.\n";
            echo "Доступные команды:\n";
            foreach (array_keys($this->commands) as $availableCommand) {
                echo "  $availableCommand\n";
            }
        }
    }

    /**
     * Регистрация команд
     * 
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands['migrate'] = 'Sura\Console\Commands\MigrateCommand';
        $this->commands['migrate:rollback'] = 'Sura\Console\Commands\MigrateRollbackCommand';
    }
}