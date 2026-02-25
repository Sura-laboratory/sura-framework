<?php
namespace Sura\Providers;

use Sura\Contracts\ServiceProviderInterface;
use Sura\Container;
use Sura\Exceptions\NotFoundException;
// use Sura\Services\Translator;
use Sura\Routing\Router;
use Sura\Http\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CoreServiceProvider implements ServiceProviderInterface
{
    /**
     * Register services in the container
     *
     * @param Container $container
     */
    public function register(Container $container): void
    {
        // Router
        $container->singleton(Router::class, function () {
            return new Router();
        });
        $container->alias('router', Router::class);

        // Request (per-request; not a shared singleton)
        $container->bind(Request::class, function () {
            return new Request();
        });

        // Response can be created directly in Kernel or injected
        // Logger
        $container->singleton(LoggerInterface::class, function () {
            return new NullLogger();
        });
    }

    /**
     * Boot services (e.g., register routes)
     *
     * @param Container $container
     * @throws NotFoundException
     */
    public function boot(Container $container): void
    {
        // Register routes here (or in a separate routes file)
        /** @var Router $router */
        $router = $container->get(Router::class);

        // example route
        // $router->get('/', function (Request $request) {
        //     $r = new \App\Http\Response();
        //     $r->write('Hello from framework root');
        //     return $r;
        // });

        // Смена языка
        $router->any('/lang/set/{locale}', function ($locale) {
            if (in_array($locale, ['en', 'ru'])) {
                setcookie('locale', $locale, time() + 3600 * 24 * 30, '/');
                $_SESSION['locale'] = $locale;
            }
            // Получаем предыдущую страницу
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';

            // Убираем возможный дублирующий слеш
            $redirectUrl = strtok($referer, '?'); // убираем query-строку, если нужно

            return (new \App\Http\Response())->redirect($redirectUrl);
        });

    }
}