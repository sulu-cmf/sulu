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

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\Uid\Uuid;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkipPath('*/var/cache')
    ->withSkipPath('*/tests/Resources/cache')
    ->withSkipPath('*/node_modules')
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.dist.neon',
    ])
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/admin/dev/App_KernelDevDebugContainer.xml')
    // ->withImportNames(importShortClasses: false)
    ->withSets([
        // Currently disabled as code is not typed enough:
        // SetList::CODE_QUALITY,
        // SymfonySetList::SYMFONY_CODE_QUALITY,
        // DoctrineSetList::DOCTRINE_CODE_QUALITY,
        // LevelSetList::UP_TO_PHP_80,
    ])
    ->withRules([
        StaticDataProviderClassMethodRector::class,
    ])
    ->withConfiguredRule(RenameMethodRector::class, [
        new MethodCallRename(Uuid::class, '__toString', 'rfc4122'),
    ])
;
