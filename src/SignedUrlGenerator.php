<?php

namespace Zenstruck\UrlSigner;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Zenstruck\UrlSigner\Exception\InvalidUrlSignature;
use Zenstruck\UrlSigner\Exception\UrlSignatureMismatch;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlGenerator implements UrlGeneratorInterface
{
    private UrlGeneratorInterface $wrapped;
    private UriSigner $signer;

    public function __construct(UrlGeneratorInterface $wrapped, UriSigner $signer)
    {
        $this->wrapped = $wrapped;
        $this->signer = $signer;
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        $url = $this->wrapped->generate($name, $parameters, $referenceType);

        return $this->signer->sign($url);
    }

    /**
     * @param string|Request $url
     *
     * @throws InvalidUrlSignature
     */
    public function validate($url): void
    {
        if ($url instanceof Request) {
            $url = self::urlFromRequest($url);
        }

        if ($this->signer->check($url)) {
            return;
        }

        throw new UrlSignatureMismatch($url);
    }

    /**
     * @param string|Request $url
     */
    public function isValid($url): bool
    {
        try {
            $this->validate($url);
        } catch (InvalidUrlSignature $e) {
            return false;
        }

        return true;
    }

    public function setContext(RequestContext $context): void
    {
        $this->wrapped->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->wrapped->getContext();
    }

    private static function urlFromRequest(Request $request): string
    {
        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs;
    }
}
