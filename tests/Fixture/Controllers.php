<?php

namespace Zenstruck\SignedUrl\Tests\Fixture;

use Symfony\Component\HttpFoundation\Response;
use Zenstruck\SignedUrl\Attribute\Signed;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Controllers
{
    public function route1(Verifier $verifier): Response
    {
        return new Response('verified: '.($verifier->isCurrentRequestVerified() ? 'yes' : 'no'));
    }

    public function route2(): Response
    {
        return new Response();
    }

    public function route3(): Response
    {
        return new Response();
    }

    #[Signed]
    public function route4(): Response
    {
        return new Response();
    }

    #[Signed(status: 404)]
    public function route5(): Response
    {
        return new Response();
    }
}
