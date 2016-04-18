<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD\Tests;

use Mekras\SystemD as f;

/**
 * Function tests.
 */
class FunctionTest extends SocketTestCase
{
    /**
     * sd_pid_notify_with_fds should:
     *
     * 1. send given data;
     * 2. remove NOTIFY_SOCKET variable.
     */
    public function test_sd_pid_notify_with_fds()
    {
        $path = $this->createSocketPath();
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        socket_bind($socket, $path);
        socket_set_option($socket, SOL_SOCKET, SO_PASSCRED, 1);
        putenv('NOTIFY_SOCKET=' . $path);

        $errno = f\sd_pid_notify_with_fds(0, 1, 'FOO=1', []);

        static::assertGreaterThan(0, $errno);
        static::assertEmpty(getenv('NOTIFY_SOCKET'));

        $data = [
            'name' => [],
            'buffer_size' => 2000,
            'controllen' => socket_cmsg_space(SOL_SOCKET, SCM_CREDENTIALS)
        ];
        $actual = socket_recvmsg($socket, $data, 0);
        static::assertGreaterThan(0, $actual, 'socket_recvmsg()');
        static::assertEquals(
            [
                'name' => null,
                'control' => [
                    [
                        'level' => SOL_SOCKET,
                        'type' => SCM_CREDENTIALS,
                        'data' => [
                            'pid' => posix_getpid(),
                            'uid' => posix_getuid(),
                            'gid' => posix_getgid()
                        ]
                    ]
                ],
                'iov' => [
                    "FOO=1\n"
                ],
                'flags' => 0
            ],
            $data
        );
    }

    /**
     * sd_pid_notify_with_fds should:
     *
     * 1. send given data;
     * 2. remove NOTIFY_SOCKET variable.
     */
    public function test_sd_pid_notify_with_fds_WithPID()
    {
        $path = $this->createSocketPath();
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        socket_bind($socket, $path);
        socket_set_option($socket, SOL_SOCKET, SO_PASSCRED, 1);
        putenv('NOTIFY_SOCKET=' . $path);

        $errno = f\sd_pid_notify_with_fds(123, 1, 'FOO=1', []);

        static::assertGreaterThan(0, $errno);
        static::assertEmpty(getenv('NOTIFY_SOCKET'));

        $data = [
            'name' => [],
            'buffer_size' => 2000,
            'controllen' => socket_cmsg_space(SOL_SOCKET, SCM_CREDENTIALS)
        ];
        $actual = socket_recvmsg($socket, $data, 0);
        static::assertGreaterThan(0, $actual, 'socket_recvmsg()');
        static::assertEquals(
            [
                'name' => null,
                'control' => [
                    [
                        'level' => SOL_SOCKET,
                        'type' => SCM_CREDENTIALS,
                        'data' => [
                            'pid' => posix_geteuid() === 0 ? 123 : posix_getpid(),
                            'uid' => posix_getuid(),
                            'gid' => posix_getgid()
                        ]
                    ]
                ],
                'iov' => [
                    "FOO=1\n"
                ],
                'flags' => 0
            ],
            $data
        );
    }

    /**
     * sd_pid_notify_with_fds should:
     *
     * 1. send given file handles;
     * 2. remove NOTIFY_SOCKET variable.
     */
    public function test_sd_pid_notify_with_fds_WithFDS()
    {
        $path = $this->createSocketPath();
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        socket_bind($socket, $path);
        putenv('NOTIFY_SOCKET=' . $path);

        $errno = f\sd_pid_notify_with_fds(0, 1, 'FOO=1', [STDIN, STDOUT]);

        static::assertGreaterThan(0, $errno);
        static::assertEmpty(getenv('NOTIFY_SOCKET'));

        $data = [
            'name' => [],
            'buffer_size' => 2000,
            'controllen' => socket_cmsg_space(SOL_SOCKET, SCM_RIGHTS, 4)
        ];
        $actual = socket_recvmsg($socket, $data, 0);
        static::assertGreaterThan(0, $actual, 'socket_recvmsg()');
        static::assertArrayHasKey('control', $data);
        static::assertArrayHasKey(0, $data['control']);
        static::assertArrayHasKey('data', $data['control'][0]);
        static::assertCount(2, $data['control'][0]['data']);
    }

    /**
     * sd_watchdog_enabled should:
     *
     * 1. return positive value if WATCHDOG_USEC is set;
     * 2. put WATCHDOG_USEC value to $usec argument;
     * 3. remove WATCHDOG_USEC and WATCHDOG_PID variables.
     */
    public function test_sd_watchdog_enabled()
    {
        putenv('WATCHDOG_USEC=10');
        putenv('WATCHDOG_PID=' . getmypid());
        $errno = f\sd_watchdog_enabled(1, $usec);

        static::assertGreaterThan(0, $errno);
        static::assertEquals(10, $usec);
        static::assertEmpty(getenv('WATCHDOG_USEC'));
        static::assertEmpty(getenv('WATCHDOG_PID'));
    }

    /**
     * sd_watchdog_enabled should return 0 if WATCHDOG_USEC is not set.
     */
    public function test_sd_watchdog_enabled_NotSet()
    {
        $errno = f\sd_watchdog_enabled(0, $usec);

        static::assertEquals(0, $errno);
    }

    /**
     * sd_watchdog_enabled should return negative value if WATCHDOG_USEC value is not an integer
     */
    public function test_sd_watchdog_enabled_NotInt()
    {
        putenv('WATCHDOG_USEC=foo');
        putenv('WATCHDOG_PID=' . getmypid());
        $errno = f\sd_watchdog_enabled(0, $usec);

        static::assertLessThan(0, $errno);
    }

    /**
     * sd_watchdog_enabled should return negative value if WATCHDOG_USEC value is negative
     */
    public function test_sd_watchdog_enabled_LessThenZero()
    {
        putenv('WATCHDOG_USEC=-1');
        putenv('WATCHDOG_PID=' . getmypid());
        $errno = f\sd_watchdog_enabled(0, $usec);

        static::assertLessThan(0, $errno);
    }

    /**
     * sd_watchdog_enabled should return negative value if WATCHDOG_PID contains invalid PID
     */
    public function test_sd_watchdog_enabled_InvalidPID()
    {
        putenv('WATCHDOG_USEC=1');
        putenv('WATCHDOG_PID=foo');
        $errno = f\sd_watchdog_enabled(0, $usec);

        static::assertLessThan(0, $errno);
    }

    /**
     * sd_watchdog_enabled should return 0 if WATCHDOG_PID contains foreign PID
     */
    public function test_sd_watchdog_enabled_ForeignPID()
    {
        putenv('WATCHDOG_USEC=1');
        putenv('WATCHDOG_PID=' . (getmypid() + 1));
        $errno = f\sd_watchdog_enabled(0, $usec);

        static::assertEquals(0, $errno);
    }
}
