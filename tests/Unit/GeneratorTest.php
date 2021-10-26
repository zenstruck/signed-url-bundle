<?php

namespace Zenstruck\UrlSigner\Tests\Unit;

use Symfony\Component\Routing\RequestContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GeneratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function can_set_and_get_request_context(): void
    {
        $generator = self::generator();
        $context = new RequestContext();

        $this->assertNotSame($context, $generator->getContext());

        $generator->setContext($context);

        $this->assertSame($context, $generator->getContext());
    }
}
