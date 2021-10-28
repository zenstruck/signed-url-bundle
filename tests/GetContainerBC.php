<?php

namespace Zenstruck\SignedUrl\Tests;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait GetContainerBC
{
    protected static function getContainer(): ContainerInterface
    {
        if (\method_exists(parent::class, 'getContainer')) {
            return parent::getContainer();
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        return self::$container;
    }
}
