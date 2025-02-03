<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle;

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluCategoryBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                CategoryInterface::class => 'sulu.model.category.class',
                CategoryMetaInterface::class => 'sulu.model.category_meta.class',
                CategoryTranslationInterface::class => 'sulu.model.category_translation.class',
                KeywordInterface::class => 'sulu.model.keyword.class',
            ],
            $container
        );
    }
}
