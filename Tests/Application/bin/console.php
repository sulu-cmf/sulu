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

// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

\set_time_limit(0);

require_once __DIR__ . '/../config/bootstrap.php';

use Sulu\Bundle\ArticleBundle\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\ErrorHandler\Debug;

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], \getenv('SYMFONY_ENV') ?: 'dev');
$debug = '0' !== \getenv('SYMFONY_DEBUG') && !$input->hasParameterOption(['--no-debug', '']) && 'prod' !== $env;

if ($debug) {
    // Clean up when sf 4.3 support is removed
    if (\class_exists(Debug::class)) {
        Debug::enable();
    } else {
        Symfony\Component\Debug\Debug::enable();
    }
}

$kernel = new Kernel($env, $debug, $suluContext);
$application = new Application($kernel);
$application->run($input);
