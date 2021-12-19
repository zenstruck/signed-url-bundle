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
     * @param \DateTimeInterface|string|int $when \DateTimeInterface: the exact time the link should expire
     *                                            string: used to construct a datetime object (ie "+1 hour")
     *                                            int: # of seconds until the link expires
     */
    public function expires($when): self
    {
        if (\is_numeric($when)) {
            $when = \DateTime::createFromFormat('U', \time() + $when);
        }

        if (\is_string($when)) {
            $when = new \DateTime($when);
        }

        if (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid expires at.', get_debug_type($when)));
        }

        $this->expiresAt = $when;
        $this->parameters[Signer::EXPIRES_AT_KEY] = $this->expiresAt->getTimestamp();

        return $this;
    }

    public function singleUse(string $token): self
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
