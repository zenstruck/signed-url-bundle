<?php

namespace Zenstruck;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\SignedUrl\DependencyInjection\ZenstruckSignedUrlExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckSignedUrlBundle extends Bundle
{
    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new ZenstruckSignedUrlExtension();
    }
}
