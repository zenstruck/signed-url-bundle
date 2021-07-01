<?php

namespace Zenstruck\UrlSigner;

use Composer\InstalledVersions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Zenstruck\UrlSigner\Exception\ExpiredUrl;
use Zenstruck\UrlSigner\Exception\InvalidUrlSignature;
use Zenstruck\UrlSigner\Exception\UrlSignatureMismatch;

/**
 * Compatibility layer for symfony/routing < 5.0.
 *
 * INTERNAL - DO NOT USE DIRECTLY.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class CompatSignedUrlGenerator implements UrlGeneratorInterface
{
    private const EXPIRES_AT_KEY = '_expires';

    private UrlGeneratorInterface $wrapped;
    private UriSigner $signer;

    public function __construct(UrlGeneratorInterface $wrapped, UriSigner $signer)
    {
        $this->wrapped = $wrapped;
        $this->signer = $signer;
    }

    /**
     * @param \DateTimeInterface|string|int $expiresAt
     */
    public function temporary($expiresAt, string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        $parameters[self::EXPIRES_AT_KEY] = self::parseDateTime($expiresAt)->getTimestamp();

        return $this->generate($name, $parameters, $referenceType);
    }

    /**
     * @param string|Request $url
     *
     * @throws InvalidUrlSignature
     */
    public function validate($url): void
    {
        $request = $url instanceof Request ? $url : Request::create($url);

        if (!$this->checkRequest($request)) {
            throw new UrlSignatureMismatch($url);
        }

        if (!$expiresAt = $request->query->getInt(self::EXPIRES_AT_KEY)) {
            return;
        }

        if (\time() > $expiresAt) {
            throw new ExpiredUrl(self::parseDateTime($expiresAt), $url);
        }
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

    protected function doGenerate($name, $parameters = [], $referenceType = self::ABSOLUTE_URL): string
    {
        $url = $this->wrapped->generate($name, $parameters, $referenceType);

        return $this->signer->sign($url);
    }

    private static function parseDateTime($datetime): \DateTimeInterface
    {
        if ($datetime instanceof \DateTimeInterface) {
            return $datetime;
        }

        if (\is_int($datetime)) {
            return \DateTime::createFromFormat('U', $datetime);
        }

        return new \DateTime($datetime);
    }

    /**
     * Compatibility layer for symfony/http-kernel < 5.1.
     *
     * @see UriSigner::checkRequest() (in symfony/http-kernel >= 5.1)
     */
    private function checkRequest(Request $request): bool
    {
        if (\method_exists($this->signer, 'checkRequest')) {
            return $this->signer->checkRequest($request);
        }

        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $this->signer->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs);
    }
}

if (\version_compare(InstalledVersions::getVersion('symfony/routing'), '5.0', '>=')) {
    /**
     * @author Kevin Bond <kevinbond@gmail.com>
     */
    final class SignedUrlGenerator extends CompatSignedUrlGenerator
    {
        public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
        {
            return $this->doGenerate($name, $parameters, $referenceType);
        }
    }
} else {
    /**
     * @author Kevin Bond <kevinbond@gmail.com>
     */
    final class SignedUrlGenerator extends CompatSignedUrlGenerator
    {
        public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_URL): string
        {
            return $this->doGenerate($name, $parameters, $referenceType);
        }
    }
}
