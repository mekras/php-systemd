<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD\Tests;

use Mekras\SystemD\SystemD;
use Mekras\SystemD\Watchdog;

/**
 * Tests for Mekras\SystemD\SystemD
 *
 * @covers Mekras\SystemD\SystemD
 */
class SystemDTest extends \PHPUnit_Framework_TestCase
{
    /**
     * SystemD::watchdog() should return singleton instance of the Watchdog class.
     */
    public function testWatchdog()
    {
        $systemd = new SystemD();

        $instance1 = $systemd->watchdog();
        $instance2 = $systemd->watchdog();

        static::assertInstanceOf(Watchdog::class, $instance1);
        static::assertSame($instance1, $instance2);
    }
}
