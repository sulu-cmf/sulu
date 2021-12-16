<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\CommandOptional;

use Massive\Bundle\BuildBundle\Command\BuildCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * This command extends the Massive BuildCommand and
 * adds a global "destroy" option and changes the name to "sulu:build".
 */
class SuluBuildCommand extends BuildCommand
{
    protected static $defaultName = 'sulu:build';

    public function configure()
    {
        parent::configure();
        $this->addOption('destroy', null, InputOption::VALUE_NONE, 'Destroy existing data');
    }
}
