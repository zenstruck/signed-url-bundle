<?php

namespace Zenstruck\SignedUrl;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Generator implements UrlGeneratorInterface
{
    private Signer $signer;

    public function __construct(Signer $signer)
    {
        $this->signer = $signer;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_URL): string
    {
        return $this->builder($name, $parameters, $referenceType);
    }

    /**
     * @param \DateTimeInterface|string|int $expiresAt \DateTimeInterface: the exact time the link should expire
     *                                                 string: used to construct a datetime object (ie "+1 hour")
     *                                                 int: # of seconds until the link expires
     */
    public function temporary($expiresAt, string $route, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        return $this->builder($route, $parameters, $referenceType)
            ->expires($expiresAt)
        ;
    }

    public function singleUse(string $token, string $route, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        return $this->builder($route, $parameters, $referenceType)
            ->singleUse($token)
        ;
    }

    public function builder(string $route, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): Builder
    {
        return new Builder($this->signer, $route, $parameters, $referenceType);
    }

    /**
     * @internal
     */
    public function setContext(RequestContext $context): void
    {
        throw new \BadMethodCallException(__METHOD__.'() not available.');
    }

    /**
     * @internal
     */
    public function getContext(): RequestContext
    {
        throw new \BadMethodCallException(__METHOD__.'() not available.');
    }
}
