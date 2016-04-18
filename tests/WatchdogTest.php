<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD\Tests;

use Mekras\SystemD\Watchdog;

/**
 * Tests for Mekras\SystemD\Watchdog
 *
 * @covers Mekras\SystemD\Watchdog
 */
class WatchdogTest extends SocketTestCase
{
    /**
     * Watchdog::getTimeout should return value of WATCHDOG_USEC variable
     */
    public function testGetTimeout()
    {
        $watchdog = new Watchdog();

        putenv('WATCHDOG_USEC=10');
        static::assertEquals(10, $watchdog->getTimeout());
    }

    /**
     * Watchdog::isEnabled should return true if WATCHDOG_USEC > 0
     */
    public function testIsEnabledYes()
    {
        $watchdog = new Watchdog();

        putenv('WATCHDOG_USEC=10');
        static::assertTrue($watchdog->isEnabled());
    }

    /**
     * Watchdog::isEnabled should return false if WATCHDOG_USEC not set
     */
    public function testIsEnabledNo()
    {
        $watchdog = new Watchdog();

        putenv('WATCHDOG_USEC');
        static::assertFalse($watchdog->isEnabled());
    }

    /**
     * Watchdog::isEnabled should return false if WATCHDOG_USEC not set
     */
    public function testPing()
    {
        $path = $this->createSocketPath();
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        socket_bind($socket, $path);
        socket_set_option($socket, SOL_SOCKET, SO_PASSCRED, 1);
        putenv('NOTIFY_SOCKET=' . $path);

        $watchdog = new Watchdog();
        $watchdog->ping();

        $data = [
            'name' => [],
            'buffer_size' => 2000,
            'controllen' => socket_cmsg_space(SOL_SOCKET, SCM_CREDENTIALS)
        ];
        $actual = socket_recvmsg($socket, $data, 0);
        static::assertGreaterThan(0, $actual, 'socket_recvmsg()');
        static::assertArrayHasKey('iov', $data);
        static::assertEquals(["WATCHDOG=1\n"], $data['iov']);
    }
}
