<?php

namespace Zenstruck\SignedUrl\Tests\Fixture;

use Symfony\Component\HttpFoundation\Response;
use Zenstruck\SignedUrl\Attribute\Signed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[Signed]
final class MoreControllers
{
    public function route6(): Response
    {
        return new Response();
    }
}
