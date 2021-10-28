<?php

namespace Zenstruck\SignedUrl\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Tests\GetContainerBC;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RouteVerificationTest extends WebTestCase
{
    use GetContainerBC;

    /**
     * @test
     */
    public function successful(): void
    {
        $client = self::createClient();

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route2'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route3'));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function failure(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route2');

        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function failure_with_custom_status(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route3');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
}
