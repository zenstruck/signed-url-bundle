<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpiredUrl extends InvalidUrlSignature
{
    private \DateTimeImmutable $expiredAt;

    public function __construct(\DateTimeImmutable $expiredAt, string $url, $message = 'URL is expired.')
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
        return 'URL is expired.';
    }
}
