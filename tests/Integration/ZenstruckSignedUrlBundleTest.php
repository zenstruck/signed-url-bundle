<?php

namespace Zenstruck\SignedUrl\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Tests\Fixture\Service;
use Zenstruck\SignedUrl\Tests\GetContainerBC;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckSignedUrlBundleTest extends KernelTestCase
{
    use GetContainerBC;

    /**
     * @test
     */
    public function services_are_autowireable(): void
    {
        $service = self::getContainer()->get(Service::class);

        $this->assertInstanceOf(Generator::class, $service->generator);
        $this->assertInstanceOf(Verifier::class, $service->verifier);
    }

    /**
     * @test
     */
    public function can_generate_and_verify_signed_url(): void
    {
        $url = self::getContainer()->get(Generator::class)->generate('route1');

        $this->assertMatchesRegularExpression('#^http://localhost/route1\?_hash=.+$#', $url);

        $this->assertTrue(self::getContainer()->get(Verifier::class)->isVerified($url));
    }
}
