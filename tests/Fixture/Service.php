<?php

namespace Zenstruck\SignedUrl\Tests\Fixture;

use Zenstruck\SignedUrl\Generator;
use Zenstruck\SignedUrl\Verifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Service
{
    public Generator $generator;
    public Verifier $verifier;

    public function __construct(Generator $generator, Verifier $verifier)
    {
        $this->generator = $generator;
        $this->verifier = $verifier;
    }
}
