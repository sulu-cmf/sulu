<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle;

use Sulu\Bundle\MediaBundle\DependencyInjection\FlysystemCompilerPass;
use Sulu\Bundle\MediaBundle\DependencyInjection\FormatCacheClearerCompilerPass;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageFormatCompilerPass;
use Sulu\Bundle\MediaBundle\DependencyInjection\ImageTransformationCompilerPass;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluMediaBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                MediaInterface::class => 'sulu.model.media.class',
                CollectionInterface::class => 'sulu.model.collection.class',
            ],
            $container
        );

        $container->addCompilerPass(new FormatCacheClearerCompilerPass());
        $container->addCompilerPass(new ImageFormatCompilerPass());
        $container->addCompilerPass(new ImageTransformationCompilerPass());
        $container->addCompilerPass(new FlysystemCompilerPass());

        parent::build($container);
    }
}
