<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpiredUrl extends InvalidUrlSignature
{
    private \DateTimeInterface $expiredAt;

    public function __construct(\DateTimeInterface $expiredAt, string $url, $message = 'URL is expired.')
    {
        $this->expiredAt = $expiredAt;

        parent::__construct($url, $message);
    }

    public function expiredAt(): \DateTimeInterface
    {
        return $this->expiredAt;
    }
}
