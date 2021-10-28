<?php

namespace Zenstruck\SignedUrl\Tests\Unit;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class VerifierTest extends UnitTestCase
{
    /**
     * @test
     */
    public function cannot_verify_current_request_if_stack_not_set(): void
    {
        $verifier = self::verifier();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current request not available.');

        $verifier->verifyCurrentRequest();
    }

    /**
     * @test
     */
    public function cannot_verify_current_request_if_stack_has_no_requests(): void
    {
        $verifier = self::verifier('1234', new RequestStack());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Current request not available.');

        $verifier->verifyCurrentRequest();
    }
}
