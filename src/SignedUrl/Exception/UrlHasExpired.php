<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UrlHasExpired extends UrlVerificationFailed
{
    private \DateTimeImmutable $expiredAt;

    public function __construct(\DateTimeImmutable $expiredAt, string $url, string $message)
    {
        $this->expiredAt = $expiredAt;

        parent::__construct($url, $message);
    }

    public function expiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function messageKey(): string
    {
        return 'URL has expired.';
    }
}
