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
        return $this->build($name, $parameters, $referenceType);
    }

    public function build(string $route, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): Builder
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
