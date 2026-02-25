<?php

namespace Sura\Providers;

use Sura\Contracts\ServiceProviderInterface;
use Sura\Container;
use Sura\Services\AuthService;
use function session_status;
use const PHP_SESSION_NONE;

class AuthServiceProvider implements ServiceProviderInterface
{
    /**
     * Регистрирует сервис аутентификации в контейнере.
     *
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void
    {
        $container->singleton('auth', function () {
            return new AuthService();
        });
    }

    /**
     *
     * @param Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
        // Запускаем сессию, если не запущена
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $auth = $container->get('auth');
        if (!$auth->check()) {
            return;
        }
        // Проверяем, есть ли user_id в сессии
        if (isset($_SESSION['user_id'])) {
            $auth->getUser(); // Загружаем пользователя
            $user = $auth->user;
            if ($user) {
                return; // Успешно восстановлен из сессии
            }
        }
    }
 
}