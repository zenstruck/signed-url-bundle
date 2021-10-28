<?php

namespace Zenstruck\SignedUrl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\SignedUrl\Exception\ExpiredUrl;
use Zenstruck\SignedUrl\Exception\InvalidUrlSignature;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;
use Zenstruck\SignedUrl\Exception\UrlSignatureMismatch;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Verifier
{
    private Signer $signer;
    private ?RequestStack $requests;

    public function __construct(Signer $signer, ?RequestStack $requests = null)
    {
        $this->signer = $signer;
        $this->requests = $requests;
    }

    /**
     * @param string|Request $url
     *
     * @throws UrlSignatureMismatch If the signed url cannot be verified
     * @throws ExpiredUrl           If the signed url is valid but expired
     * @throws UrlAlreadyUsed       If the url is single use and has already been used
     */
    public function verify($url, ?string $singleUseToken = null): void
    {
        $this->signer->verify($url, $singleUseToken);
    }

    /**
     * Attempt to verify the current request.
     *
     * @throws \RuntimeException    If no current request available
     * @throws UrlSignatureMismatch If the current request cannot be verified
     * @throws ExpiredUrl           If the current request is valid but expired
     * @throws UrlAlreadyUsed       If the current request is single use and has already been used
     */
    public function verifyCurrentRequest(?string $singleUseToken = null): void
    {
        if (!$this->requests || !$request = $this->requests->getCurrentRequest()) {
            throw new \RuntimeException('Current request not available.');
        }

        $this->verify($request, $singleUseToken);
    }

    /**
     * @param string|Request $url
     *
     * @return bool true if verified, false if not
     */
    public function isVerified($url, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($url, $singleUseToken);
        } catch (InvalidUrlSignature $e) {
            return false;
        }

        return true;
    }

    /**
     * @return bool true if verified, false if not
     *
     * @throws \RuntimeException If no current request available
     */
    public function isCurrentRequestVerified(?string $singleUseToken = null): bool
    {
        try {
            $this->verifyCurrentRequest($singleUseToken);
        } catch (InvalidUrlSignature $e) {
            return false;
        }

        return true;
    }
}
