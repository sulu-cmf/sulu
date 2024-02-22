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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Link\ContentLinkProvider;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

/**
 * @extends ContentLinkProvider<ArticleDimensionContentInterface, ArticleInterface>
 */
class ArticleLinkProvider extends ContentLinkProvider
{
    public function __construct(
        ContentManagerInterface $contentManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($contentManager, $structureMetadataFactory, $entityManager, ArticleInterface::class);
    }

    public function getConfiguration(): LinkConfiguration
    {
        return LinkConfigurationBuilder::create()
            ->setTitle('Example')
            ->setResourceKey(ArticleInterface::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['id'])
            ->setOverlayTitle('Select Example')
            ->setEmptyText('No example selected')
            ->setIcon('su-document')
            ->getLinkConfiguration();
    }
}
