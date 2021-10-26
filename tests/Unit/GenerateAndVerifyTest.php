<?php

namespace Zenstruck\UrlSigner\Tests\Unit;

use Symfony\Component\HttpFoundation\Request;
use Zenstruck\UrlSigner\Exception\ExpiredUrl;
use Zenstruck\UrlSigner\Exception\UrlSignatureMismatch;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenerateAndVerifyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function can_generate_and_validate_signed_url(): void
    {
        $url = self::generator()->generate('route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url));

        self::verifier()->verify($url);
    }

    /**
     * @test
     */
    public function validation_fails_if_signature_is_invalid(): void
    {
        $url = self::generator('secret1')->generate('route1');
        $verifier = self::verifier('secret2');

        $this->assertFalse($verifier->isVerified($url));

        try {
            $verifier->verify($url);
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
        $url = self::generator()->temporary($expiresAt, 'route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_expires=\d+&_hash=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url));

        self::verifier()->verify($url);
    }

    /**
     * @test
     * @dataProvider invalidExpirations
     */
    public function validation_fails_if_url_has_expired($expiresAt, \DateTime $expected): void
    {
        $url = self::generator()->temporary($expiresAt, 'route1');

        $this->assertFalse(self::verifier()->isVerified($url));

        try {
            self::verifier()->verify($url);
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
        $verifier = self::verifier('secret2');

        $this->assertFalse($verifier->isVerified($url));

        $this->expectException(UrlSignatureMismatch::class);

        $verifier->verify($url);
    }

    /**
     * @test
     */
    public function can_pass_request_object_to_validation_methods(): void
    {
        $generator = self::generator();

        $request = Request::create($generator->generate('route1'));
        $this->assertTrue(self::verifier()->isVerified($request));

        self::verifier()->verify($request);
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
}
