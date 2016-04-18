<?php
/**
 * Object-oriented PHP interface to systemd
 *
 * @author    Михаил Красильников <m.krasilnikov@yandex.ru>
 * @license   http://opensource.org/licenses/MIT MIT
 */
namespace Mekras\SystemD;

/*
 * Maybe someday it will be added to this https://github.com/systemd/php-systemd
 */

if (!defined('EINVAL')) {
    /**
     * Invalid argument (POSIX.1)
     *
     * @link http://man7.org/linux/man-pages/man3/errno.3.html
     */
    define('EINVAL', 22);
}

/**
 * sd_notify PHP implementation
 *
 * @param bool   $unset_environment
 * @param string $state
 *
 * @return int
 *
 * @link https://www.freedesktop.org/software/systemd/man/sd_notify.html
 */
function sd_notify($unset_environment, $state)
{
    return sd_pid_notify_with_fds(0, $unset_environment, $state, []);
}

/**
 * sd_pid_notify_with_fds PHP implementation
 *
 * @param int    $pid FIXME currently not usable!
 * @param bool   $unset_environment
 * @param string $state
 * @param array  $fds
 *
 * @return int
 *
 * @link https://github.com/systemd/systemd/blob/master/src/libsystemd/sd-daemon/sd-daemon.c
 */
function sd_pid_notify_with_fds($pid, $unset_environment, $state, array $fds)
{
    $state = trim($state);

    if ('' === $state) {
        $r = -EINVAL;
        goto finish;
    }

    $e = getenv('NOTIFY_SOCKET');
    if (!$e) {
        return 0;
    }

    /* Must be an abstract socket, or an absolute path */
    if (strlen($e) < 2 || (strpos($e, '@') !== 0 && strpos($e, '/') !== 0)) {
        $r = -EINVAL;
        goto finish;
    }

    $fd = socket_create(AF_UNIX, SOCK_DGRAM /* |SOCK_CLOEXEC */, 0);
    if (!$fd) {
        $r = -1 * socket_last_error();
        goto finish;
    }

    $msghdr = [
        'name' => [
            'path' => $e
        ],
        'iov' => [
            $state . "\n"
        ],
        'control' => []
    ];
    if (strpos($msghdr['name']['path'], '@') === 0) {
        $msghdr['name'][0] = "\x00";
    }

    $pid = (int) $pid;
    $have_pid = $pid && getmypid() !== $pid;

    if (count($fds) > 0 || $have_pid) {

        if (count($fds)) {
            $msghdr['control'][] = [
                'level' => SOL_SOCKET,
                'type' => SCM_RIGHTS,
                'data' => $fds
            ];
        }

        if ($have_pid) {
            $msghdr['control'][] = [
                'level' => SOL_SOCKET,
                'type' => SCM_CREDENTIALS,
                'data' => [
                    'pid' => $pid,
                    'uid' => getmyuid(),
                    'gid' => getmygid()
                ]
            ];
        }
    }

    /* First try with fake ucred data, as requested */
    if (@socket_sendmsg($fd, $msghdr, MSG_NOSIGNAL) !== false) {
        $r = 1;
        goto finish;
    }

    /* If that failed, try with our own ucred instead */
    if ($have_pid) {

        $msghdr['control'] = [];

        if (@socket_sendmsg($fd, $msghdr, MSG_NOSIGNAL) !== false) {
            $r = 1;
            goto finish;
        }
    }

    $r = -1 * socket_last_error($fd);

    finish:

    if (isset($fd) && $fd) {
        socket_close($fd);
    }

    if ($unset_environment) {
        putenv('NOTIFY_SOCKET');
    }

    return $r;
}

/**
 * sd_watchdog_enabled PHP implementation
 *
 * @param bool $unset_environment
 * @param int  $usec
 *
 * @return int
 *
 * @link https://github.com/systemd/systemd/blob/master/src/libsystemd/sd-daemon/sd-daemon.c
 */
function sd_watchdog_enabled($unset_environment, &$usec)
{
    $r = 0;
    $p = null;

    $s = getenv('WATCHDOG_USEC');
    if (!$s) {
        goto finish;
    }

    if (!filter_var($s, FILTER_VALIDATE_INT)) {
        $r = -EINVAL;
        goto finish;
    }
    $u = (int) $s;

    if ($u <= 0) {
        $r = -EINVAL;
        goto finish;
    }

    $p = getenv('WATCHDOG_PID');
    if ($p) {
        $pid = (int) $p;
        if ($pid < 1) {
            $r = -EINVAL;
            goto finish;
        }

        /* Is this for us? */
        if (getmypid() !== $pid) {
            $r = 0;
            goto finish;
        }
    }

    $usec = $u;

    $r = 1;

    finish:

    if ($unset_environment && $s) {
        putenv('WATCHDOG_USEC');
    }
    if ($unset_environment && $p) {
        putenv('WATCHDOG_PID');
    }

    return $r;
}
