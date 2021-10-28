<?php

namespace Zenstruck\SignedUrl\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Tests\GetContainerBC;

/**
 * @requires PHP 8
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedAttributeVerificationTest extends WebTestCase
{
    use GetContainerBC;

    /**
     * @test
     */
    public function successful(): void
    {
        $client = self::createClient();

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route4'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route5'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route6'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function failure(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route4');

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function failure_with_custom_status(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route5');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function failure_with_attribute_on_class(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route6');

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }
}
