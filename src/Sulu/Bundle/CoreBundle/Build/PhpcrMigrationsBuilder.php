<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use PHPCR\Migrations\MigratorFactory;
use PHPCR\Migrations\VersionStorage;

/**
 * Initialize the migrations when Sulu is built.
 *
 * After a repository has been intialized all migrations should be added to the
 * repository.
 */
class PhpcrMigrationsBuilder extends SuluBuilder
{
    private \PHPCR\Migrations\MigratorFactory $migratorFactory;

    private \PHPCR\Migrations\VersionStorage $versionStorage;

    public function __construct(MigratorFactory $migratorFactory, VersionStorage $versionStorage)
    {
        $this->migratorFactory = $migratorFactory;
        $this->versionStorage = $versionStorage;
    }

    public function getName()
    {
        return 'phpcr_migrations';
    }

    public function getDependencies()
    {
        return ['phpcr'];
    }

    public function build()
    {
        $migrator = $this->migratorFactory->getMigrator();

        if (false === $this->versionStorage->hasVersioningNode()) {
            $migrator->initialize();
        }
    }
}
