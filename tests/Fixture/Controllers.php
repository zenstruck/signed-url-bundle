<?php

namespace Zenstruck\SignedUrl\Tests\Fixture;

use Symfony\Component\HttpFoundation\Response;
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
}
