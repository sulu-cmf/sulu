<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\DependencyInjection\SuluPreviewExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Integrates preview into symfony.
 *
 * @final
 */
class SuluPreviewBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->buildPersistence(
            [
                PreviewLinkInterface::class => 'sulu.model.preview_link.class',
            ],
            $container
        );
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new SuluPreviewExtension();
    }
}
