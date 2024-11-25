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

namespace Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Link;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Link\ContentLinkProvider;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;

/**
 * @extends ContentLinkProvider<ExampleDimensionContent, Example>
 */
class ExampleLinkProvider extends ContentLinkProvider
{
    public function __construct(
        ContentManagerInterface $contentManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($contentManager, $structureMetadataFactory, $entityManager, Example::class);
    }

    public function getConfiguration(): LinkConfiguration
    {
        return LinkConfigurationBuilder::create()
            ->setTitle('Example')
            ->setResourceKey(Example::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['id'])
            ->setOverlayTitle('Select Example')
            ->setEmptyText('No example selected')
            ->setIcon('su-document')
            ->getLinkConfiguration();
    }
}
