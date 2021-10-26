<?php

namespace Zenstruck\UrlSigner\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Zenstruck\UrlSigner\Generator;
use Zenstruck\UrlSigner\Signer;
use Zenstruck\UrlSigner\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class UnitTestCase extends TestCase
{
    protected static function generator(string $secret = '1234'): Generator
    {
        return new Generator(self::signer($secret));
    }

    protected static function verifier(string $secret = '1234', ?RequestStack $stack = null): Verifier
    {
        return new Verifier(self::signer($secret), $stack);
    }

    private static function signer(string $secret): Signer
    {
        $routes = new RouteCollection();
        $routes->add('route1', new Route('/route1'));

        return new Signer(new UrlGenerator($routes, new RequestContext()), $secret);
    }
}
