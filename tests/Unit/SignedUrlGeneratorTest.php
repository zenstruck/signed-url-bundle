<?php

namespace Zenstruck\UrlSigner\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Zenstruck\UrlSigner\Exception\ExpiredUrl;
use Zenstruck\UrlSigner\Exception\UrlSignatureMismatch;
use Zenstruck\UrlSigner\SignedUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function can_generate_and_validate_signed_url(): void
    {
        $generator = self::generator();

        $url = $generator->generate('route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=.+$#', $url);
        $this->assertTrue($generator->isValid($url));

        $generator->validate($url);
    }

    /**
     * @test
     */
    public function validation_fails_if_signature_is_invalid(): void
    {
        $url = self::generator('secret1')->generate('route1');
        $generator = self::generator('secret2');

        $this->assertFalse($generator->isValid($url));

        try {
            $generator->validate($url);
        } catch (UrlSignatureMismatch $e) {
            $this->assertSame($url, $e->url());

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     * @dataProvider validExpirations
     */
    public function can_generate_and_validate_temporary_url($expiresAt): void
    {
        $generator = self::generator();

        $url = $generator->temporary($expiresAt, 'route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_expires=\d+&_hash=.+$#', $url);
        $this->assertTrue($generator->isValid($url));

        $generator->validate($url);
    }

    /**
     * @test
     * @dataProvider invalidExpirations
     */
    public function validation_fails_if_url_has_expired($expiresAt, \DateTime $expected): void
    {
        $generator = self::generator();
        $url = $generator->temporary($expiresAt, 'route1');

        $this->assertFalse($generator->isValid($url));

        try {
            $generator->validate($url);
        } catch (ExpiredUrl $e) {
            $this->assertSame($url, $e->url());
            $this->assertContains($expected->getTimestamp(), [
                $e->expiredAt()->getTimestamp() - 1,
                $e->expiredAt()->getTimestamp(),
                $e->expiredAt()->getTimestamp() + 1,
            ]);

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     * @dataProvider validExpirations
     */
    public function validation_fails_if_url_is_not_expired_but_signature_is_invalid($expiresAt): void
    {
        $url = self::generator('secret1')->temporary($expiresAt, 'route1');
        $generator = self::generator('secret2');

        $this->assertFalse($generator->isValid($url));

        $this->expectException(UrlSignatureMismatch::class);

        $generator->validate($url);
    }

    public static function validExpirations(): iterable
    {
        yield [new \DateTime('+1 week')];
        yield [\time() + 100];
        yield ['+1 hour'];
    }

    public static function invalidExpirations(): iterable
    {
        yield [$time = new \DateTime('-1 week'), $time];
        yield [$time = \time() - 100, \DateTime::createFromFormat('U', $time)];
        yield [$time = '-1 hour', new \DateTime($time)];
    }

    /**
     * @test
     */
    public function can_pass_request_object_to_validation_methods(): void
    {
        $generator = self::generator();

        $request = Request::create($generator->generate('route1'));
        $this->assertTrue($generator->isValid($request));

        $generator->validate($request);
    }

    /**
     * @test
     */
    public function can_set_and_get_request_context(): void
    {
        $generator = self::generator();
        $context = new RequestContext();

        $this->assertNotSame($context, $generator->getContext());

        $generator->setContext($context);

        $this->assertSame($context, $generator->getContext());
    }

    private static function generator(string $secret = '1234'): SignedUrlGenerator
    {
        $routes = new RouteCollection();
        $routes->add('route1', new Route('/route1'));

        return new SignedUrlGenerator(
            new UrlGenerator($routes, new RequestContext()),
            new UriSigner($secret)
        );
    }
}
