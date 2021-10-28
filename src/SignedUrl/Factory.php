<?php

namespace Zenstruck\SignedUrl;

use Zenstruck\SignedUrl;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Factory
{
    private Signer $signer;
    private string $route;
    private array $parameters;
    private int $referenceType;
    private ?\DateTimeInterface $expiresAt = null;

    /**
     * @internal
     */
    public function __construct(Signer $signer, string $route, array $parameters, int $referenceType)
    {
        $this->signer = $signer;
        $this->route = $route;
        $this->parameters = $parameters;
        $this->referenceType = $referenceType;
    }

    public function __toString(): string
    {
        return $this->create();
    }

    /**
     * @param \DateTimeInterface|string|int $when
     */
    public function expiresAt($when): self
    {
        $this->expiresAt = Signer::parseDateTime($when);
        $this->parameters[Signer::EXPIRES_AT_KEY] = $this->expiresAt->getTimestamp();

        return $this;
    }

    /**
     * @param string|callable():string $token
     */
    public function singleUse($token): self
    {
        $this->parameters[Signer::SINGLE_USE_TOKEN_KEY] = $this->signer->hash($token);

        return $this;
    }

    public function create(): SignedUrl
    {
        return new SignedUrl(
            $this->signer->sign($this->route, $this->parameters, $this->referenceType),
            $this->expiresAt,
            isset($this->parameters[Signer::SINGLE_USE_TOKEN_KEY])
        );
    }
}
