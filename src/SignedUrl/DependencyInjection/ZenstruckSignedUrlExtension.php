<?php

namespace Zenstruck\SignedUrl\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Signer;
use Zenstruck\SignedUrl\Verifier;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckSignedUrlExtension extends ConfigurableExtension implements ConfigurationInterface
{
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('zenstruck_signed_url');

        $builder->getRootNode()
            ->children()
                ->scalarNode('key')
                    ->info('The key to sign urls with')
                    ->defaultValue('%kernel.secret%')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $builder;
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register('zenstruck_signed_url.signer', Signer::class)
            ->setArguments([new Reference('router'), $mergedConfig['key']])
        ;
        $container->register(Generator::class)->setArguments([new Reference('zenstruck_signed_url.signer')]);
        $container->register(Verifier::class)->setArguments([
            new Reference('zenstruck_signed_url.signer'),
            new Reference('request_stack'),
        ]);
    }
}
