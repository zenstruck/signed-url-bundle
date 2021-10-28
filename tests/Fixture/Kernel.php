<?php

namespace Zenstruck\SignedUrl\Tests\Fixture;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zenstruck\ZenstruckSignedUrlBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const SECRET = 'S3CRET';

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ZenstruckSignedUrlBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->register(Service::class)->setAutowired(true)->setPublic(true);
        $c->register(Controllers::class)->addTag('controller.service_arguments');
        $c->register(MoreControllers::class)->addTag('controller.service_arguments');
        $c->register('logger', NullLogger::class);

        $c->loadFromExtension('framework', [
            'secret' => self::SECRET,
            'test' => true,
            'router' => ['utf8' => true],
        ]);
        $c->loadFromExtension('zenstruck_signed_url', [
            'route_verification' => true,
        ]);
    }

    /**
     * @param RouteCollectionBuilder|RoutingConfigurator $routes
     */
    protected function configureRoutes($routes): void
    {
        if ($routes instanceof RouteCollectionBuilder) {
            $routes->add('/route1', Controllers::class.'::route1', 'route1');
            $routes->add('/route2', Controllers::class.'::route2', 'route2')->addOptions(['signed' => true]);
            $routes->add('/route3', Controllers::class.'::route3', 'route3')->addOptions(['signed' => 404]);
            $routes->add('/route4', Controllers::class.'::route4', 'route4');
            $routes->add('/route5', Controllers::class.'::route5', 'route5');
            $routes->add('/route6', MoreControllers::class.'::route6', 'route6');

            return;
        }

        $routes->add('route1', '/route1')->controller(Controllers::class.'::route1');
        $routes->add('route2', '/route2')->controller(Controllers::class.'::route2')->options(['signed' => true]);
        $routes->add('route3', '/route3')->controller(Controllers::class.'::route3')->options(['signed' => 404]);
        $routes->add('route4', '/route4')->controller(Controllers::class.'::route4');
        $routes->add('route5', '/route5')->controller(Controllers::class.'::route5');
        $routes->add('route6', '/route6')->controller(MoreControllers::class.'::route6');
    }
}
