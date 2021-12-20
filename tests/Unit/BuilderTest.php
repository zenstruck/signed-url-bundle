<?php

namespace Zenstruck\SignedUrl\Tests\Unit;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BuilderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function expires_must_be_date_timeable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('stdClass is not a valid expires at.');

        self::generator()->build('foo')->expires(new \stdClass());
    }
}
