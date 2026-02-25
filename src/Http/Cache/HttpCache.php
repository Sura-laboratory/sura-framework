<?php

declare(strict_types=1);

namespace Sura\Http\Cache;

use Sura\Http\Request;
use Sura\Http\Response;

/**
 * HTTP caching implementation for handling cache headers and responses.
 */
class HttpCache
{
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var Response
     */
    private Response $response;

    /**
     * @var int Default cache time in seconds (1 hour)
     */
    private int $defaultTtl = 3600;

    /**
     * HttpCache constructor.
     *
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Handle HTTP caching logic.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->addCacheHeaders();
    }

    /**
     * Add cache headers to the response.
     *
     * @param int|null $ttl Time to live in seconds
     * @return void
     */
    private function addCacheHeaders(int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;

        // Set Cache-Control header
        $this->response->headers->set('Cache-Control', sprintf('public, max-age=%d', $ttl));

        // Set Expires header
        $this->response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT');

        // Set Last-Modified header
        $this->response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');

        // Check for If-Modified-Since header and return 304 if not modified
        $ifModifiedSince = $this->request->headers->get('If-Modified-Since');
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= time() - $ttl) {
            $this->response->setNotModified();
            $this->response->send();
            exit();
        }
    }

    /**
     * Set default TTL for cache.
     *
     * @param int $ttl
     * @return self
     */
    public function withDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }
}