<?php

namespace Zenstruck\UrlSigner;

use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Generator implements UrlGeneratorInterface
{
    /** @internal */
    public const EXPIRES_AT_KEY = '_expires';

    private UriSigner $signer;
    private UrlGeneratorInterface $router;

    public function __construct(UriSigner $signer, UrlGeneratorInterface $router)
    {
        $this->signer = $signer;
        $this->router = $router;
    }

    public function generate(string $name, $parameters = [], $referenceType = self::ABSOLUTE_URL): string
    {
        return $this->signer->sign(
            $this->router->generate($name, $parameters, $referenceType)
        );
    }

    /**
     * @param \DateTimeInterface|string|int $expiresAt
     */
    public function temporary($expiresAt, string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        $parameters[self::EXPIRES_AT_KEY] = self::parseDateTime($expiresAt)->getTimestamp();

        return $this->generate($name, $parameters, $referenceType);
    }

    /**
     * @internal
     */
    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    /**
     * @internal
     */
    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    /**
     * @internal
     */
    public static function parseDateTime($timestamp): \DateTimeInterface
    {
        if ($timestamp instanceof \DateTimeInterface) {
            return $timestamp;
        }

        if (\is_int($timestamp)) {
            return \DateTime::createFromFormat('U', $timestamp);
        }

        return new \DateTime($timestamp);
    }
}
