<?php

namespace Zenstruck\SignedUrl\Tests\Unit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\SignedUrl\Exception\ExpiredUrl;
use Zenstruck\SignedUrl\Exception\SingleUseUrlMismatch;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;
use Zenstruck\SignedUrl\Exception\UrlSignatureMismatch;

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
                $e->expiredAt()->getTimestamp() - 2,
                $e->expiredAt()->getTimestamp() - 1,
                $e->expiredAt()->getTimestamp(),
                $e->expiredAt()->getTimestamp() + 1,
                $e->expiredAt()->getTimestamp() + 2,
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

        $this->expectException(UrlSignatureMismatch::class);

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function verify_current_request_expired_failure(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->temporary('yesterday', 'route1')));

        $verifier = self::verifier('1234', $stack);

        $this->assertFalse($verifier->isCurrentRequestVerified());

        $this->expectException(ExpiredUrl::class);

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function verify_current_request_single_use_failure(): void
    {
        $generator = self::generator('1234');

        $stack = new RequestStack();
        $stack->push(Request::create($generator->singleUse('token1', 'route1')));

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

        $url = self::generator()->singleUse($token, 'route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url, $token));

        self::verifier()->verify($url, $token);
    }

    /**
     * @test
     */
    public function single_use_token_can_be_callable(): void
    {
        $token = fn() => '1234';

        $url = self::generator()->singleUse($token, 'route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue(self::verifier()->isVerified($url, $token));

        self::verifier()->verify($url, $token);
    }

    /**
     * @test
     */
    public function single_use_verification_fails_on_token_mismatch(): void
    {
        $url = self::generator()->singleUse('token1', 'route1');
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

        $this->expectException(SingleUseUrlMismatch::class);
        $this->expectExceptionMessage('Expected single user url.');

        $verifier->verify($url, 'token');
    }

    /**
     * @test
     */
    public function single_use_verification_if_url_single_use_but_no_token_passed(): void
    {
        $url = self::generator()->singleUse('token', 'route1');
        $verifier = self::verifier();

        $this->assertFalse($verifier->isVerified($url));

        $this->expectException(SingleUseUrlMismatch::class);
        $this->expectExceptionMessage('Given url is single use but this was not expected.');

        $verifier->verify($url);
    }

    /**
     * @test
     */
    public function full_featured_url(): void
    {
        $url = self::generator()->factory('route1')->expiresAt('tomorrow')->singleUse('token1')->create();
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
        $url = self::generator('secret1')->factory('route1')->expiresAt('yesterday')->singleUse('token1');

        $this->expectException(UrlSignatureMismatch::class);

        self::verifier('secret2')->verify($url, 'token2');
    }

    /**
     * @test
     */
    public function expired_url_fails_before_single_use(): void
    {
        $url = self::generator()->factory('route1')->expiresAt('yesterday')->singleUse('token1');

        $this->expectException(ExpiredUrl::class);

        self::verifier()->verify($url, 'token2');
    }

    /**
     * @test
     */
    public function single_use_fails_last(): void
    {
        $url = self::generator()->factory('route1')->expiresAt('tomorrow')->singleUse('token1');

        $this->expectException(UrlAlreadyUsed::class);

        self::verifier()->verify($url, 'token2');
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
