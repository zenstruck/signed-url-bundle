<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UrlAlreadyUsed extends UrlVerificationFailed
{
    public function messageKey(): string
    {
        return 'URL has already been used.';
    }
}
