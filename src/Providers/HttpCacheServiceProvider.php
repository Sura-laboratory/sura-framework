<?php

declare(strict_types=1);

namespace Sura\Providers;

use Sura\Contracts\ServiceProviderInterface;
use Sura\Http\Cache\HttpCache;
use Sura\Http\Request;
use App\Http\Response;

/**
 * Service provider for HTTP caching.
 */
class HttpCacheServiceProvider implements ServiceProviderInterface
{
    /**
     * Register caching services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(HttpCache::class, function () {
            return new HttpCache($this->app->make(Request::class), $this->app->make(Response::class));
        });
    }

    /**
     * Boot HTTP caching logic.
     *
     * @return void
     */
    public function boot(): void
    {
        /** @var HttpCache $httpCache */
        $httpCache = $this->app->make(HttpCache::class);
        
        // Apply caching only in production environment
        if ($this->app->isProduction()) {
            $httpCache->handle();
        }
    }
}