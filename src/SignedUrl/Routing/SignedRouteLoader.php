<?php

namespace Zenstruck\SignedUrl\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedRouteLoader implements LoaderInterface
{
    private LoaderInterface $inner;

    public function __construct(LoaderInterface $inner)
    {
        $this->inner = $inner;
    }

    public function load($resource, ?string $type = null): RouteCollection
    {
        /** @var RouteCollection $routes */
        $routes = $this->inner->load($resource, $type);

        foreach ($routes as $route) {
            if ($signed = $route->getOption('signed')) {
                $route->addDefaults(['_signed' => $signed]);
            }
        }

        return $routes;
    }

    public function supports($resource, ?string $type = null): bool
    {
        return $this->inner->supports($resource, $type);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->inner->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->inner->setResolver($resolver);
    }
}
