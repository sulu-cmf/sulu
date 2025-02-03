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

namespace Sulu\Bundle\TrashBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Infrastructure\Symfony\DependencyInjection\SuluTrashExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluTrashBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                TrashItemInterface::class => 'sulu.model.trash_item.class',
            ],
            $container
        );
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new SuluTrashExtension();
    }
}
