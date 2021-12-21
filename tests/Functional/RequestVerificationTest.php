<?php

namespace Zenstruck\SignedUrl\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Tests\GetContainerBC;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RequestVerificationTest extends WebTestCase
{
    use GetContainerBC;

    /**
     * @test
     */
    public function passed_verification(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route1');

        $client->request('GET', self::getContainer()->get(Generator::class)->generate('route1'));

        $this->assertSame('verified: yes', $client->getResponse()->getContent());

        $client->request('GET', self::getContainer()->get(Generator::class)->build('route1')->expires('tomorrow'));

        $this->assertSame('verified: yes', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function failed_verification(): void
    {
        $client = self::createClient();
        $client->request('GET', '/route1');

        $this->assertSame('verified: no', $client->getResponse()->getContent());

        $client->request('GET', '/route1?_hash=foo');

        $this->assertSame('verified: no', $client->getResponse()->getContent());

        $client->request('GET', self::getContainer()->get(Generator::class)->build('route1')->expires('yesterday'));

        $this->assertSame('verified: no', $client->getResponse()->getContent());

        $client->request('GET', self::getContainer()->get(Generator::class)->singleUse('token', 'route1'));

        $this->assertSame('verified: no', $client->getResponse()->getContent());
    }
}
