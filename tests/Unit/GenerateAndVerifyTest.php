<?php

namespace Zenstruck\SignedUrl\Tests\Unit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;
use Zenstruck\SignedUrl\Exception\UrlHasExpired;
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

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
        } catch (UrlVerificationFailed $e) {
            $this->assertSame($url, $e->url());
            $this->assertSame('URL Verification failed.', $e->messageKey());

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
        $url = self::generator()->build('route1')->expires($expiresAt);

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
        $url = (string) self::generator()->build('route1')->expires($expiresAt);

        $this->assertFalse(self::verifier()->isVerified($url));

        try {
            self::verifier()->verify($url);
        } catch (UrlHasExpired $e) {
            $this->assertSame($url, $e->url());
            $this->assertSame('URL has expired.', $e->messageKey());

            // TODO the following is brittle
            $this->assertContains($expected->getTimestamp(), [
                $e->expiredAt()->getTimestamp() - 3,
                $e->expiredAt()->getTimestamp() - 2,
                $e->expiredAt()->getTimestamp() - 1,
                $e->expiredAt()->getTimestamp(),
                $e->expiredAt()->getTimestamp() + 1,
                $e->expiredAt()->getTimestamp() + 2,
                $e->expiredAt()->getTimestamp() + 3,
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
        $url = self::generator('secret1')->build('route1')->expires($expiresAt);
        $verifier = self::verifier('secret2');

        $this->assertFalse($verifier->isVerified($url));

        try {
            $verifier->verify($url);
        } catch (UrlVerificationFailed $e) {
            $this->assertSame(UrlVerificationFailed::class, \get_class($e));

            return;
        }

        $this->fail('Exception not thrown.');
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

    /**
     * @test
     */
    public function can_verify_current_request(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->generate('route1')));

        $verifier = self::verifier('1234', $stack);

        $this->assertTrue($verifier->isCurrentRequestVerified());

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function verify_current_request_invalid_signature_failure(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->generate('route1')));

        $verifier = self::verifier('4321', $stack);

        $this->assertFalse($verifier->isCurrentRequestVerified());

        $this->expectException(UrlVerificationFailed::class);

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function verify_current_request_expired_failure(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->build('route1')->expires('yesterday')));

        $verifier = self::verifier('1234', $stack);

        $this->assertFalse($verifier->isCurrentRequestVerified());

        $this->expectException(UrlHasExpired::class);

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function verify_current_request_single_use_failure(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->build('route1')->singleUse('token1')));

        $verifier = self::verifier('1234', $stack);

        $this->assertFalse($verifier->isCurrentRequestVerified());

        $this->expectException(UrlAlreadyUsed::class);

        $verifier->verifyCurrentRequest('token2');
    }

    /**
     * @test
     */
    public function can_generate_and_validate_single_use_url(): void
    {
        $token = '1234';

        $url = self::generator()->build('route1')->singleUse($token);

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url, $token));

        self::verifier()->verify($url, $token);
    }

    /**
     * @test
     */
    public function can_generate_and_validate_single_use_url_with_extra_query_parameters(): void
    {
        $token = '1234';

        $url = self::generator()->build('route1', ['foo' => 'bar'])->singleUse($token);

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+foo=bar$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url, $token));

        self::verifier()->verify($url, $token);
    }

    /**
     * @test
     */
    public function single_use_token_can_be_stringable_object(): void
    {
        $token = new class() {
            public function __toString(): string
            {
                return '1234';
            }
        };

        $url = self::generator()->build('route1')->singleUse($token);

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url, $token));

        self::verifier()->verify($url, $token);
    }

    /**
     * @test
     */
    public function single_use_verification_fails_on_token_mismatch(): void
    {
        $url = (string) self::generator()->build('route1')->singleUse('token1');
        $verifier = self::verifier();

        $this->assertFalse($verifier->isVerified($url, 'token2'));

        try {
            $verifier->verify($url, 'token2');
        } catch (UrlAlreadyUsed $e) {
            $this->assertSame($url, $e->url());

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function single_use_verification_fails_if_url_not_single_use(): void
    {
        $url = self::generator()->generate('route1');
        $verifier = self::verifier();

        $this->assertFalse($verifier->isVerified($url, 'token'));

        $this->expectException(UrlVerificationFailed::class);
        $this->expectExceptionMessage('Expected single user url.');

        $verifier->verify($url, 'token');
    }

    /**
     * @test
     */
    public function single_use_verification_if_url_single_use_but_no_token_passed(): void
    {
        $url = self::generator()->build('route1')->singleUse('token');
        $verifier = self::verifier();

        $this->assertFalse($verifier->isVerified($url));

        $this->expectException(UrlVerificationFailed::class);
        $this->expectExceptionMessage('Given URL is single use but this was not expected.');

        $verifier->verify($url);
    }

    /**
     * @test
     */
    public function full_featured_url(): void
    {
        $url = self::generator()->build('route1')->expires('tomorrow')->singleUse('token1')->create();
        $verifier = self::verifier();

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_expires=\d+&_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue($verifier->isVerified($url, 'token1'));
        $verifier->verify($url, 'token1');
    }

    /**
     * @test
     */
    public function url_signature_mismatch_always_fails_first(): void
    {
        $url = self::generator('secret1')->build('route1')->expires('yesterday')->singleUse('token1');

        try {
            self::verifier('secret2')->verify($url, 'token2');
        } catch (UrlVerificationFailed $e) {
            $this->assertSame(UrlVerificationFailed::class, \get_class($e));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function expired_url_fails_before_single_use(): void
    {
        $url = self::generator()->build('route1')->expires('yesterday')->singleUse('token1');

        $this->expectException(UrlHasExpired::class);

        self::verifier()->verify($url, 'token2');
    }

    /**
     * @test
     */
    public function single_use_fails_last(): void
    {
        $url = self::generator()->build('route1')->expires('tomorrow')->singleUse('token1');

        $this->expectException(UrlAlreadyUsed::class);

        self::verifier()->verify($url, 'token2');
    }

    public static function validExpirations(): iterable
    {
        yield [new \DateTime('+1 week')];
        yield [100];
        yield ['100'];
        yield ['+1 hour'];
    }

    public static function invalidExpirations(): iterable
    {
        yield [$time = new \DateTime('-1 week'), $time];
        yield [$time = -100, \DateTime::createFromFormat('U', \time() + $time)];
        yield [$time = '-100', \DateTime::createFromFormat('U', \time() + (int) $time)];
        yield [$time = '-1 hour', new \DateTime($time)];
    }
}
