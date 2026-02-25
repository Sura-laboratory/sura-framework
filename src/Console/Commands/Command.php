<?php

namespace Sura\Console\Commands;

/**
 * Абстрактный класс для всех консольных команд
 */
abstract class Command
{
    /**
     * Выполнение команды
     * 
     * @param array $arguments Аргументы команды
     * @return void
     */
    abstract public function execute(array $arguments): void;
}