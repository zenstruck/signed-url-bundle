<?php

namespace Zenstruck\SignedUrl\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class InvalidUrlSignature extends \RuntimeException
{
    private string $url;

    public function __construct(string $url, $message = 'Invalid URL Signature.')
    {
        parent::__construct($message);

        $this->url = $url;
    }

    final public function url(): string
    {
        return $this->url;
    }

    /**
     * Unchanging message key for translations.
     */
    public function messageKey(): string
    {
        return 'Invalid URL Signature.';
    }
}
