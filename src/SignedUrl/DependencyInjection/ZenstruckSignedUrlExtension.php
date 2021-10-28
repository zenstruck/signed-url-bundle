<?php

namespace Zenstruck\SignedUrl\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Signer;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckSignedUrlExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register('zenstruck_signed_url.signer', Signer::class)
            ->setArguments([new Reference('router'), '%kernel.secret%'])
        ;
        $container->register(Generator::class)->setArguments([new Reference('zenstruck_signed_url.signer')]);
        $container->register(Verifier::class)->setArguments([
            new Reference('zenstruck_signed_url.signer'),
            new Reference('request_stack'),
        ]);
    }
}
