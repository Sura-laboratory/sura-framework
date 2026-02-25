<?php
namespace Sura\Contracts;

use Sura\Container;

interface ServiceProviderInterface
{
    public function register(Container $container): void;
    public function boot(Container $container): void;
}