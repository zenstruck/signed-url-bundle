<?php

namespace Zenstruck\UrlSigner;

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
        return $this->signer->sign($name, $parameters, $referenceType);
    }

    /**
     * @param \DateTimeInterface|string|int $expiresAt
     */
    public function temporary($expiresAt, string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        $parameters[Signer::EXPIRES_AT_KEY] = Signer::parseDateTime($expiresAt)->getTimestamp();

        return $this->generate($name, $parameters, $referenceType);
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
