<?php

namespace Zenstruck\UrlSigner;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Signer
{
    public const EXPIRES_AT_KEY = '_expires';

    private UriSigner $uriSigner;
    private UrlGeneratorInterface $router;

    public function __construct(UriSigner $uriSigner, UrlGeneratorInterface $router)
    {
        $this->uriSigner = $uriSigner;
        $this->router = $router;
    }

    public function sign(string $route, array $parameters, int $referenceType): string
    {
        return $this->uriSigner->sign($this->router->generate($route, $parameters, $referenceType));
    }

    public function check(Request $request): bool
    {
        if (\method_exists($this->uriSigner, 'checkRequest')) {
            return $this->uriSigner->checkRequest($request);
        }

        // compatibility layer for symfony/http-kernel < 5.1.
        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $this->uriSigner->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs);
    }

    public static function parseDateTime($timestamp): \DateTimeInterface
    {
        if ($timestamp instanceof \DateTimeInterface) {
            return $timestamp;
        }

        if (\is_int($timestamp)) {
            return \DateTime::createFromFormat('U', $timestamp);
        }

        return new \DateTime($timestamp);
    }
}
