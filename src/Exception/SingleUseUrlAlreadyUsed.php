<?php

namespace Zenstruck\UrlSigner\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleUseUrlAlreadyUsed extends InvalidUrlSignature
{
    public function __construct(string $url, $message = 'Single use URL has already used.')
    {
        parent::__construct($url, $message);
    }
}
