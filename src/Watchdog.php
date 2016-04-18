<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD;

use Mekras\SystemD\Exception\Exception;
use Mekras\SystemD\Exception\ExceptionFactory;

/**
 * The Watchdog service.
 *
 * @api
 * @link  https://www.freedesktop.org/software/systemd/man/systemd.service.html#WatchdogSec=
 * @since 1.0
 */
class Watchdog
{
    /**
     * Return Watchdog timeout.
     *
     * @return int Timeout in seconds. 0 means that watchdog not enabled.
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @link  isEnabled()
     * @since 1.0
     */
    public function getTimeout()
    {
        $sec = 0;
        $errno = sd_watchdog_enabled(0, $sec);
        if ($errno < 0) {
            throw ExceptionFactory::createFromCode($errno);
        }

        return $sec;
    }

    /**
     * Return true if Watchdog is enabled.
     *
     * @return bool
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @link  getTimeout()
     * @since 1.0
     */
    public function isEnabled()
    {
        return $this->getTimeout() > 0;
    }

    /**
     * Send the "keep-alive ping".
     *
     * @return void
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     *
     * @since 1.0
     */
    public function ping()
    {
        $errno = sd_notify(0, 'WATCHDOG=1');
        if ($errno < 0) {
            throw ExceptionFactory::createFromCode($errno);
        }
    }
}
