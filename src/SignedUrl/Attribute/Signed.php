<?php

namespace Zenstruck\SignedUrl\Attribute;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Signed
{
    public int $status;

    public function __construct(int $status = 403)
    {
        $this->status = $status;
    }
}
