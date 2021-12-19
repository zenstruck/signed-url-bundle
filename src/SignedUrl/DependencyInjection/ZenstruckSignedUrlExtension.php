<?php

namespace Zenstruck\SignedUrl\DependencyInjection;

use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\SignedUrl\Attribute\Signed;
use Zenstruck\SignedUrl\EventListener\VerifySignedRouteSubscriber;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Routing\SignedRouteLoader;
use Zenstruck\SignedUrl\Signer;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
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
                ->scalarNode('secret')
                    ->info('The secret key to sign urls with')
                    ->defaultValue('%kernel.secret%')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('route_verification')
                    ->info(\sprintf('Enable auto route verification (trigger with "signed" route option or "%s" attribute)', Signed::class))
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $builder;
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register('zenstruck_signed_url.signer', Signer::class)
            ->setArguments([new Reference('router'), $mergedConfig['secret']])
        ;
        $container->register(Generator::class)->setArguments([new Reference('zenstruck_signed_url.signer')]);
        $container->register(Verifier::class)->setArguments([
            new Reference('zenstruck_signed_url.signer'),
            new Reference('request_stack'),
        ]);

        if ($mergedConfig['route_verification']) {
            $container->register('zenstruck_signed_url.verify_route', VerifySignedRouteSubscriber::class)
                ->setArguments([new Reference(ContainerInterface::class)])
                ->addTag('kernel.event_subscriber')
                ->addTag('container.service_subscriber')
            ;
            $container->register('zenstruck_signed_url.route_loader', SignedRouteLoader::class)
                ->setDecoratedService('routing.loader')
                ->setArguments([new Reference('zenstruck_signed_url.route_loader.inner')])
            ;
        }
    }
}
