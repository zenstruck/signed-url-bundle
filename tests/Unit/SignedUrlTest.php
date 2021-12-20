<?php

namespace Zenstruck\SignedUrl\Tests\Unit;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlTest extends UnitTestCase
{
    /**
     * @test
     */
    public function simple(): void
    {
        $url = self::generator()->build('route1')->create();

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=.+$#', $url);
        $this->assertFalse($url->isTemporary());
        $this->assertNull($url->expiresAt());
        $this->assertFalse($url->isSingleUse());
    }

    /**
     * @test
     */
    public function temporary(): void
    {
        $expected = new \DateTime('tomorrow');
        $url = self::generator()->build('route1')->expires($expected)->create();

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_expires=\d+&_hash=.+$#', $url);
        $this->assertTrue($url->isTemporary());
        $this->assertSame($expected->getTimestamp(), $url->expiresAt()->getTimestamp());
        $this->assertFalse($url->isSingleUse());
    }

    /**
     * @test
     */
    public function single_use(): void
    {
        $url = self::generator()->build('route1')->singleUse('token1')->create();

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertFalse($url->isTemporary());
        $this->assertNull($url->expiresAt());
        $this->assertTrue($url->isSingleUse());
    }

    /**
     * @test
     */
    public function full_featured(): void
    {
        $expected = new \DateTime('tomorrow');
        $url = self::generator()->build('route1')->expires($expected)->singleUse('token1')->create();

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_expires=\d+&_hash=[\w\%]+&_token=.+$#', $url);
        $this->assertTrue($url->isTemporary());
        $this->assertSame($expected->getTimestamp(), $url->expiresAt()->getTimestamp());
        $this->assertTrue($url->isSingleUse());
    }
}
