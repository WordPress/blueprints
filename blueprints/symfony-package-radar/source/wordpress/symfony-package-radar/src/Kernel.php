<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'playground-demo',
            'http_method_override' => false,
            'http_client' => [
                'default_options' => [],
            ],
            'php_errors' => [
                'log' => true,
            ],
        ]);

        $container->extension('twig', [
            'paths' => [dirname(__DIR__) . '/templates'],
            'strict_variables' => true,
        ]);

        // Force Symfony onto PHP streams so the demo works with Playground
        // networking without requiring curl_share_init() from the wasm build.
        $container->services()
            ->set(NativeHttpClient::class)
            ->args([['timeout' => 5, 'max_duration' => 5]])
            ->alias(HttpClientInterface::class, NativeHttpClient::class)

            ->load('App\\', '../src/')
            ->autowire()
            ->autoconfigure();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../src/Controller/', 'attribute');
    }
}
