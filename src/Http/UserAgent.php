<?php
namespace Sura\Http;

/**
 * Класс для определения типа устройства пользователя по User-Agent
 * 
 * Позволяет определять, с какого типа устройства выполнен запрос:
 * мобильное, планшетное или десктопное.
 */
class UserAgent
{
    private string $userAgent;
    private ?bool $isMobile = null;
    private ?bool $isTablet = null;


    /**
     * Конструктор класса UserAgent
     *
     * @param string|null $userAgent Строка User-Agent. Если не передана, берется из $_SERVER['HTTP_USER_AGENT']
     */
    public function __construct(string $userAgent = '')
    {
        $this->userAgent = $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->isMobile();
        $this->isTablet();


    }

    /**
     * Проверить, является ли устройство мобильным телефоном
     *
     * @return bool true, если устройство является мобильным телефоном, иначе false
     */
    public function isMobile(): bool
    {
        if ($this->isMobile !== null) {
            return $this->isMobile;
        }

        $patterns = [
            '/Android.*Mobile/i',
            '/iPhone/i',
            '/BlackBerry/i',
            '/Opera Mini/i',
            '/IEMobile/i',
            '/Mobile Safari/i',
            '/Mobile/i',
            '/Mobi/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->userAgent)) {
                return $this->isMobile = true;
            }
        }

        return $this->isMobile = false;
    }

    /**
     * Проверить, является ли устройство планшетом
     *
     * @return bool true, если устройство является планшетом, иначе false
     */
    public function isTablet(): bool
    {
        $patterns = [
            '/iPad/i',
            '/Android(?!.*Mobile)/i', // Здесь важно не экранировать!
            '/Tablet/i',
            '/Kindle/i',
            '/PlayBook/i',
            '/Silk/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->userAgent)) {
                return $this->isTablet = true;
            }
        }

        return false;
    }

    /**
     * Проверить, является ли устройство десктопным компьютером
     *
     * @return bool true, если устройство является десктопным, иначе false
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile && !$this->isTablet;
    }

    /**
     * Получить тип устройства
     *
     * Возвращает строковый идентификатор типа устройства: 'mobile', 'tablet' или 'desktop'
     *
     * @return string Тип устройства
     */
    public function getDeviceType(): string
    {
        if ($this->isMobile()) {
            return 'mobile';
        }
        
        if ($this->isTablet()) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    public function getBrowser(): string
    {
        if ($this->isChrome()) return 'chrome';
        if ($this->isFirefox()) return 'firefox';
        if ($this->isSafari()) return 'safari';
        if ($this->isEdge()) return 'edge';
        if ($this->isIE()) return 'ie';

        return 'unknown';
    }    

    public function isBot(): bool
    {
        $bots = [
            '/Googlebot/i',
            '/Bingbot/i',
            '/Slurp/i',         // Yahoo
            '/DuckDuckBot/i',
            '/Baiduspider/i',
            '/YandexBot/i',
            '/ia_archiver/i',   // Alexa
            '/facebookexternalhit/i',
            '/Twitterbot/i'
        ];

        foreach ($bots as $pattern) {
            if (preg_match($pattern, $this->userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить оригинальную строку User-Agent
     *
     * @return string Строка User-Agent
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Проверить, является ли браузер Chrome
     *
     * @return bool true, если браузер является Chrome, иначе false
     */
    public function isChrome(): bool
    {
        return (bool) preg_match('/Chrome\/\d+/', $this->userAgent) &&
               !preg_match('/Edge let/', $this->userAgent);
    }

    /**
     * Проверить, является ли браузер Firefox
     *
     * @return bool true, если браузер является Firefox, иначе false
     */
    public function isFirefox(): bool
    {
        return (bool) preg_match('/Firefox\/\d+/', $this->userAgent);
    }

    /**
     * Проверить, является ли браузер Safari
     *
     * @return bool true, если браузер является Safari, иначе false
     */
    public function isSafari(): bool
    {
        return (bool) preg_match('/Safari\/\d+/', $this->userAgent) &&
               !preg_match('/Chrome\/\d+/', $this->userAgent);
    }

    /**
     * Проверить, является ли браузер Edge
     *
     * @return bool true, если браузер является Edge, иначе false
     */
    public function isEdge(): bool
    {
        return (bool) preg_match('/Edg(e|a|ing)\/\d+/', $this->userAgent);
    }

    /**
     * Проверить, является ли браузер Internet Explorer
     *
     * @return bool true, если браузер является Internet Explorer, иначе false
     */
    public function isIE(): bool
    {
        return (bool) preg_match('/Trident\/|MSIE \d/', $this->userAgent);
    }
}