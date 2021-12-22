<?php

namespace Zenstruck\SignedUrl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;
use Zenstruck\SignedUrl\Exception\UrlHasExpired;
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

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
     * @param string|null    $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *                                       {@see https://github.com/zenstruck/signed-url-bundle#single-use-urls}
     *
     * @throws UrlHasExpired         If the signed url is valid but expired
     * @throws UrlAlreadyUsed        If the url is single use and has already been used
     * @throws UrlVerificationFailed If the url fails verification
     */
    public function verify($url, ?string $singleUseToken = null): void
    {
        $this->signer->verify($url instanceof Request ? $url : Request::create($url), $singleUseToken);
    }

    /**
     * Attempt to verify the current request.
     *
     * @param string|null $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *                                    {@see https://github.com/zenstruck/signed-url-bundle#single-use-urls}
     *
     * @throws \RuntimeException     If no current request available
     * @throws UrlHasExpired         If the signed url is valid but expired
     * @throws UrlAlreadyUsed        If the url is single use and has already been used
     * @throws UrlVerificationFailed If the url fails verification
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
     * @param string|null    $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *                                       {@see https://github.com/zenstruck/signed-url-bundle#single-use-urls}
     *
     * @return bool true if verified, false if not
     */
    public function isVerified($url, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($url, $singleUseToken);
        } catch (UrlVerificationFailed $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string|null $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *                                    {@see https://github.com/zenstruck/signed-url-bundle#single-use-urls}
     *
     * @return bool true if verified, false if not
     *
     * @throws \RuntimeException If no current request available
     */
    public function isCurrentRequestVerified(?string $singleUseToken = null): bool
    {
        try {
            $this->verifyCurrentRequest($singleUseToken);
        } catch (UrlVerificationFailed $e) {
            return false;
        }

        return true;
    }
}
