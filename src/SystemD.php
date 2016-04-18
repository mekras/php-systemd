<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD;

/**
 * Systemd daemon interface.
 *
 * @api
 * @since 1.0
 */
class SystemD
{
    /**
     * The Watchdog service instance.
     *
     * @var Watchdog|null
     */
    private $watchdog = null;

    /**
     * Return Watchdog instance.
     *
     * @return Watchdog
     *
     * @link  https://www.freedesktop.org/software/systemd/man/systemd.service.html#WatchdogSec=
     * @since 1.0
     */
    public function watchdog()
    {
        if (null === $this->watchdog) {
            $this->watchdog = new Watchdog();
        }

        return $this->watchdog;
    }
}
