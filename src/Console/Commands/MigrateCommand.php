<?php

namespace Sura\Console\Commands;

use Sura\Database\MigrationRunner;

/**
 * Команда для запуска миграций
 */
class MigrateCommand extends Command
{
    /**
     * Выполнение команды
     * 
     * @param array $arguments Аргументы команды
     * @return void
     */
    public function execute(array $arguments): void
    {
        $runner = new MigrationRunner();
        $runner->run();
    }
}