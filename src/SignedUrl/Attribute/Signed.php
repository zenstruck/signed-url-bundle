<?php

namespace Zenstruck\SignedUrl\Attribute;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Signed
{
    public function __construct(public int $status = 403)
    {
    }
}
