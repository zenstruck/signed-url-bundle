<?php

namespace Zenstruck\SignedUrl\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Zenstruck\SignedUrl\DependencyInjection\ZenstruckSignedUrlExtension;
use Zenstruck\SignedUrl\Generator;
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
        $this->assertContainerBuilderHasService(Generator::class);
        $this->assertContainerBuilderHasService(Verifier::class);
    }

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckSignedUrlExtension()];
    }
}
