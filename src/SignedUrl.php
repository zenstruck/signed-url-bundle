<?php

namespace Zenstruck;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrl
{
    private string $url;
    private ?\DateTimeImmutable $expiresAt;
    private bool $singleUse;

    /**
     * @internal
     */
    public function __construct(string $url, ?\DateTimeImmutable $expiresAt, bool $singleUse)
    {
        $this->url = $url;
        $this->expiresAt = $expiresAt;
        $this->singleUse = $singleUse;
    }

    public function __toString(): string
    {
        return $this->url;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isTemporary(): bool
    {
        return null !== $this->expiresAt;
    }

    public function isSingleUse(): bool
    {
        return $this->singleUse;
    }
}
