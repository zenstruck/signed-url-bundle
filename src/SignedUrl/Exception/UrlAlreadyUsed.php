<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UrlAlreadyUsed extends InvalidUrlSignature
{
    public function __construct(string $url, $message = 'Single use URL has already used.')
    {
        parent::__construct($url, $message);
    }
}
