<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use Doctrine\Bundle\PHPCRBundle\Command\WorkspaceImportCommand as BaseWorkspaceImportCommand;

class WorkspaceImportCommand extends BaseWorkspaceImportCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->getDefinition()->getOption('uuid-behavior')->setDefault('throw');
    }
}
