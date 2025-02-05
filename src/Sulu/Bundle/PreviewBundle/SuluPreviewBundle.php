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
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluPreviewBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
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

        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_preview.preview_object_provider_registry',
                'sulu_preview.object_provider',
                0,
                'provider-key'
            )
        );
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return new SuluPreviewExtension();
    }
}
