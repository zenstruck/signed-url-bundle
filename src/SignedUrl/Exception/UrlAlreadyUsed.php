<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UrlAlreadyUsed extends InvalidUrlSignature
{
    public function __construct(string $url, $message = 'URL has already been used.')
    {
        parent::__construct($url, $message);
    }

    public function messageKey(): string
    {
        return 'URL has already been used.';
    }
}
