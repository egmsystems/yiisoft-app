<?php

declare(strict_types=1);

use App\Environment;

//require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/egm/Composer.php';
require_once Egm\Composer::vendorDir() . '/autoload.php';

Environment::prepare();
