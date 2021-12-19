<?php

namespace Zenstruck\SignedUrl\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Zenstruck\SignedUrl\Exception\InvalidUrlSignature;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class VerifySignedRouteSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$check = $request->attributes->get('_route_params', [])['_signed'] ?? false) {
            return;
        }

        try {
            $this->container->get(Verifier::class)->verify($request);
        } catch (InvalidUrlSignature $e) {
            throw new HttpException(\is_int($check) ? $check : 403, $e->messageKey(), $e);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [ControllerEvent::class => 'onController'];
    }

    public static function getSubscribedServices(): array
    {
        return [Verifier::class];
    }
}
