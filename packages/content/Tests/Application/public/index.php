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

use Sulu\Bundle\ContentBundle\Tests\Application\Kernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

// Webserver should run under dev for development
$_SERVER['APP_ENV'] = 'dev';
$_ENV['APP_ENV'] = 'dev';

require \dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    \umask(0000);
    Debug::enable();
}

$suluContext = SuluKernel::CONTEXT_WEBSITE;

if (\preg_match('/^\/admin(\/|$)/', $_SERVER['REQUEST_URI'])) {
    $suluContext = SuluKernel::CONTEXT_ADMIN;
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'], $suluContext);

Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
