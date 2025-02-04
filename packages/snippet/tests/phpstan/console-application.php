<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Article\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require \dirname(__DIR__) . '/Application/config/bootstrap.php';
$kernel = new Kernel(
    $_SERVER['APP_ENV'], // @phpstan-ignore argument.type
    (bool) $_SERVER['APP_DEBUG'],
    Kernel::CONTEXT_ADMIN,
);
return new Application($kernel);
