<?php

namespace Zenstruck\SignedUrl\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Zenstruck\SignedUrl\DependencyInjection\ZenstruckSignedUrlExtension;
use Zenstruck\SignedUrl\EventListener\VerifySignedRouteSubscriber;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Routing\SignedRouteLoader;
use Zenstruck\SignedUrl\Signer;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckSignedUrlExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function default_setup(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService('zenstruck_signed_url.signer', Signer::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('zenstruck_signed_url.signer', 1, '%kernel.secret%');
        $this->assertContainerBuilderHasService(Generator::class);
        $this->assertContainerBuilderHasService(Verifier::class);
        $this->assertContainerBuilderNotHasService('zenstruck_signed_url.verify_route');
        $this->assertContainerBuilderNotHasService('zenstruck_signed_url.route_loader');
    }

    /**
     * @test
     */
    public function can_configure_secret(): void
    {
        $this->load(['secret' => 'custom-key']);

        $this->assertContainerBuilderHasService('zenstruck_signed_url.signer', Signer::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('zenstruck_signed_url.signer', 1, 'custom-key');
    }

    /**
     * @test
     */
    public function can_enable_route_verification(): void
    {
        $this->load(['route_verification' => true]);

        $this->assertContainerBuilderHasService('zenstruck_signed_url.verify_route', VerifySignedRouteSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_signed_url.verify_route', 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_signed_url.verify_route', 'container.service_subscriber');
        $this->assertContainerBuilderHasService('zenstruck_signed_url.route_loader', SignedRouteLoader::class);
    }

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckSignedUrlExtension()];
    }
}
