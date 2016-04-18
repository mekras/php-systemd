# php-systemd

Object-oriented interface to systemd.

[![Latest Stable Version](https://poser.pugx.org/mekras/php-systemd/v/stable.png)](https://packagist.org/packages/mekras/php-systemd)
[![License](https://poser.pugx.org/mekras/php-systemd/license.png)](https://packagist.org/packages/mekras/php-systemd)
[![Build Status](https://travis-ci.org/mekras/php-systemd.svg?branch=develop)](https://travis-ci.org/mekras/php-systemd)
[![Coverage Status](https://coveralls.io/repos/mekras/php-systemd/badge.svg?branch=master&service=github)](https://coveralls.io/github/mekras/php-systemd?branch=master)


## Watchdog

systemd
[watchdog](https://www.freedesktop.org/software/systemd/man/systemd.service.html#WatchdogSec=)
restarts frozen service.

```php
use Mekras\SystemD\SystemD;

$systemd = new SystemD();
$keepAlive = $systemd->watchdog()->isEnabled();

//...

while (true) {
    if ($keepAlive) {
        $systemd->watchdog()->ping();
    }
    //...
}
