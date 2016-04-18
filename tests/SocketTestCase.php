<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD\Tests;

/**
 * Superclass for socket-based tests
 */
abstract class SocketTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Test sockets paths
     *
     * @var string[]
     */
    private $socketsPaths = [];

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        putenv('NOTIFY_SOCKET');
        putenv('WATCHDOG_PID');
        putenv('WATCHDOG_USEC');

        foreach ($this->socketsPaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Create new test socket pathname.
     *
     * @return string
     */
    protected function createSocketPath()
    {
        $path = tempnam(sys_get_temp_dir(), 'php-systemd-test-');
        @unlink($path);
        $path .= '.sock';
        $this->socketsPaths[] = $path;

        return $path;
    }
}
