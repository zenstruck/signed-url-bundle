<?php

namespace Zenstruck\UrlSigner\Tests\Unit;

use Symfony\Component\Routing\RequestContext;
use Zenstruck\UrlSigner\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GeneratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function cannot_set_context(): void
    {
        $generator = self::generator();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(\sprintf('%s::setContext() not available.', Generator::class));

        $generator->setContext(new RequestContext());
    }

    /**
     * @test
     */
    public function cannot_get_context(): void
    {
        $generator = self::generator();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(\sprintf('%s::getContext() not available.', Generator::class));

        $generator->getContext();
    }
}
