<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class UrlVerificationFailed extends \RuntimeException
{
    private string $url;

    public function __construct(string $url, string $message)
    {
        parent::__construct($message);

        $this->url = $url;
    }

    final public function url(): string
    {
        return $this->url;
    }

    /**
     * User-friendly/safe reason. This return value must not change (per exception class)
     * so it can be used for translations.
     */
    public function messageKey(): string
    {
        return 'URL Verification failed.';
    }
}
