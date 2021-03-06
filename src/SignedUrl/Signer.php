<?php

namespace Zenstruck\SignedUrl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\SignedUrl\Exception\UrlAlreadyUsed;
use Zenstruck\SignedUrl\Exception\UrlHasExpired;
use Zenstruck\SignedUrl\Exception\UrlVerificationFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Signer
{
    private const SIGNATURE_KEY = '_hash';
    private const EXPIRES_AT_KEY = '_expires';
    private const SINGLE_USE_TOKEN_KEY = '_token';

    private UriSigner $uriSigner;
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router, string $secret)
    {
        $this->uriSigner = new UriSigner($secret, self::SIGNATURE_KEY);
        $this->router = $router;
    }

    public function sign(string $route, array $parameters, int $referenceType, ?\DateTimeImmutable $expiresAt, ?string $singleUseToken): string
    {
        if ($expiresAt) {
            $parameters[self::EXPIRES_AT_KEY] = $expiresAt->getTimestamp();
        }

        $url = $this->router->generate($route, $parameters, $referenceType);

        if ($singleUseToken) {
            $url = self::singleUseSigner($singleUseToken)->sign($url);
        }

        return $this->uriSigner->sign($url);
    }

    public function verify(Request $request, ?string $singleUseToken): void
    {
        $url = $request->getUri();

        if (!self::isSignatureValid($this->uriSigner, $request)) {
            throw new UrlVerificationFailed($url, 'URL missing signature or could not be verified.');
        }

        $expiresAt = $request->query->getInt(self::EXPIRES_AT_KEY);

        if ($expiresAt && \time() > $expiresAt) {
            throw new UrlHasExpired(\DateTimeImmutable::createFromFormat('U', $expiresAt), $url, 'URL has expired.');
        }

        $singleUseHash = $request->query->get(self::SINGLE_USE_TOKEN_KEY);

        if (!$singleUseHash && !$singleUseToken) {
            return;
        }

        if ($singleUseHash && !$singleUseToken) {
            throw new UrlVerificationFailed($url, 'Given URL is single use but this was not expected.');
        }

        if (!$singleUseHash && $singleUseToken) {
            throw new UrlVerificationFailed($url, 'Expected single user url.');
        }

        if (!self::isSignatureValid(self::singleUseSigner($singleUseToken), self::removeSignatureKey($request))) {
            throw new UrlAlreadyUsed($url, 'URL has already been used.');
        }
    }

    private static function removeSignatureKey(Request $request): Request
    {
        \parse_str($request->getQueryString(), $params);

        unset($params[self::SIGNATURE_KEY]);

        $request->server->set('QUERY_STRING', \http_build_query($params));

        return $request;
    }

    private static function isSignatureValid(UriSigner $uriSigner, Request $request): bool
    {
        if (\method_exists($uriSigner, 'checkRequest')) {
            return $uriSigner->checkRequest($request);
        }

        // compatibility layer for symfony/http-kernel < 5.1.
        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $uriSigner->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs);
    }

    private static function singleUseSigner(string $token): UriSigner
    {
        return new UriSigner($token, self::SINGLE_USE_TOKEN_KEY);
    }
}
