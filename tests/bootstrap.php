<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', 'testing');
define('KIOSK_PIN', '1234');
define('DAY_LOCK_TIME', '23:59');
define('BLOCK_WEEKENDS', true);
define('MARKING_MODE', 'open');

require APP_ROOT . '/src/helpers/Security.php';
