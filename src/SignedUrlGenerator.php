<?php

namespace Zenstruck\UrlSigner;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Zenstruck\UrlSigner\Exception\ExpiredUrl;
use Zenstruck\UrlSigner\Exception\InvalidUrlSignature;
use Zenstruck\UrlSigner\Exception\UrlSignatureMismatch;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlGenerator implements UrlGeneratorInterface
{
    private const EXPIRES_AT_KEY = '_expires';

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

        if (!$this->signer->checkRequest($request)) {
            throw new UrlSignatureMismatch($url);
        }

        if (!$expiresAt = $request->query->getInt(self::EXPIRES_AT_KEY)) {
            return;
        }

        if ((new \DateTime('now'))->getTimestamp() > $expiresAt) {
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
}
