<?php
namespace Sura\Middleware;

use Sura\Http\Request;
use App\Http\Response;

/**
 * Interface MiddlewareInterface
 * @package Sura\Middleware
 */
interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable $next function(Request): Response
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}