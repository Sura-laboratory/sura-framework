<?php

namespace Sura\Providers;

use Sura\Contracts\ServiceProviderInterface;
use Sura\Container;
use Sura\Services\Translator;
use Sura\Services\ViewRenderer;

class I18nServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Транслятор
        $container->singleton('translator', function () {
            $locale = $this->detectLocale();
            return new Translator($locale, __DIR__ . '/../../resources/lang');
        });
    }

    public function boot(Container $container): void
    {
        // Можно ничего не делать
    }

    private function detectLocale(): string
    {
        // Проверяем сессию
        if (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
            $langs = require __DIR__ . '/../../config/langs.php';
            if (array_key_exists($locale, $langs)) {
                return $locale;
            }
        }

        // Проверяем HTTP_ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $langs = require __DIR__ . '/../../config/langs.php';
            if (array_key_exists($lang, $langs)) {
                return $lang;
            }
        }

        // Язык по умолчанию
        return 'en';
    }
}