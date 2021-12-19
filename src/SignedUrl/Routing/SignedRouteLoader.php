<?php

namespace Zenstruck\SignedUrl\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Zenstruck\SignedUrl\Attribute\Signed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class SignedRouteLoader implements LoaderInterface
{
    private LoaderInterface $inner;

    public function __construct(LoaderInterface $inner)
    {
        $this->inner = $inner;
    }

    public function load($resource, $type = null): RouteCollection
    {
        /** @var RouteCollection $routes */
        $routes = $this->inner->load($resource, $type);

        foreach ($routes as $route) {
            self::parseSignedAttribute($route);

            if ($signed = $route->getOption('signed')) {
                $route->addDefaults(['_signed' => $signed]);
            }
        }

        return $routes;
    }

    public function supports($resource, $type = null): bool
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

    private static function parseSignedAttribute(Route $route): void
    {
        if (\PHP_VERSION_ID < 80000) {
            return;
        }

        try {
            $method = new \ReflectionMethod($route->getDefault('_controller'));
        } catch (\ReflectionException $e) {
            return;
        }

        $attribute = $method->getAttributes(Signed::class)[0] ?? $method->getDeclaringClass()->getAttributes(Signed::class)[0] ?? null;

        if ($attribute) {
            $route->addOptions(['signed' => $attribute->newInstance()->status]);
        }
    }
}
