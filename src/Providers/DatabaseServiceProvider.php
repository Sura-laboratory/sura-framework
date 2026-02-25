<?php

namespace Sura\Providers;

use Sura\Contracts\ServiceProviderInterface;
use Sura\Container;
use mysqli;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton('db', function () {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $database = $_ENV['DB_NAME'] ?? 'test';
            $port = (int)($_ENV['DB_PORT'] ?? 3306);

            $connection = new mysqli($host, $username, $password, $database, $port);

            if ($connection->connect_error) {
                throw new \RuntimeException('Database connection failed: ' . $connection->connect_error);
            }

            return $connection;
        });

        // Обёртка для удобной работы
        $container->singleton('db.query', function () use ($container) {
            $connection = $container->get('db');
            return new \Sura\Database\QueryBuilder($connection);
        });        
    }

    public function boot(Container $container): void
    {
        $db = $container->get('db');
        $db->set_charset('utf8');
    }
}