<?php

namespace Zenstruck\SignedUrl;

use Zenstruck\SignedUrl;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Builder
{
    private Signer $signer;
    private string $route;
    private array $parameters;
    private int $referenceType;
    private ?\DateTimeImmutable $expiresAt = null;
    private ?string $singleUseToken = null;

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
            $when = \DateTimeImmutable::createFromFormat('U', \time() + $when);
        }

        if (\is_string($when)) {
            $when = new \DateTimeImmutable($when);
        }

        if ($when instanceof \DateTime) {
            $when = \DateTimeImmutable::createFromMutable($when);
        }

        if (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid expires at.', get_debug_type($when)));
        }

        $this->expiresAt = $when;

        return $this;
    }

    public function singleUse(string $token): self
    {
        $this->singleUseToken = $token;

        return $this;
    }

    public function create(): SignedUrl
    {
        return new SignedUrl(
            $this->signer->sign(
                $this->route,
                $this->parameters,
                $this->referenceType,
                $this->expiresAt,
                $this->singleUseToken
            ),
            $this->expiresAt,
            (bool) $this->singleUseToken
        );
    }
}
