<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD\Exception;

/**
 * Exception factory.
 *
 * @since 1.0
 */
class ExceptionFactory
{
    /**
     * Create exception from error code
     *
     * @param int $errno
     *
     * @return Exception
     *
     * @throws \InvalidArgumentException if $errno >= 0
     *
     * @since 1.0
     */
    public static function createFromCode($errno)
    {
        if ($errno >= 0) {
            throw new \InvalidArgumentException(
                sprintf('Argument 1 for %s should be less than zero', __METHOD__)
            );
        }
        if (EINVAL === $errno) {
            return new UnexpectedValueException('Unexpected value');
        }
        return new RuntimeException(sprintf('Unknown error: %d', $errno));
    }
}
