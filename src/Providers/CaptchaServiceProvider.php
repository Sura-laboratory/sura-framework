<?php

namespace Sura\Providers;

use Sura\Container;
use Sura\Contracts\ServiceProviderInterface;

class CaptchaServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton('validator', function () {
            return new Validator();
        });
    }

    public function boot(Container $container): void
    {
        // Можно загрузить правила, языковые файлы и т.п.
    }
}