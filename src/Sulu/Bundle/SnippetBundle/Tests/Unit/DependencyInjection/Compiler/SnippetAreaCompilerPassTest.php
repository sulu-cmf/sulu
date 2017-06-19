<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection\Compiler;

use Sulu\Bundle\SnippetBundle\DependencyInjection\Compiler\SnippetAreaCompilerPass;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Test snippet area compiler pass.
 */
class SnippetAreaCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @var StructureMetadataFactoryInterface
     */
    protected $structureFactory;

    public function testWithoutAreas()
    {
        $compiler = new SnippetAreaCompilerPass();

        $structureMetaData1 = $this->createStructureMetaData('test');
        $structureMetaData2 = $this->createStructureMetaData('hotel');

        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->structureFactory->getStructures('snippet')->willReturn([
            $structureMetaData1->reveal(),
            $structureMetaData2->reveal(),
        ]);

        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->container->get('sulu_content.structure.factory')->willReturn(
            $this->structureFactory->reveal()
        );
        $this->container->getParameter('sulu_core.locales')->willReturn(['en', 'de']);

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'test' => [
                    'key' => 'test',
                    'template' => 'test',
                    'title' => [
                        'de' => 'Test DE',
                        'en' => 'Test EN',
                    ],
                ],
                'hotel' => [
                    'key' => 'hotel',
                    'template' => 'hotel',
                    'title' => [
                        'de' => 'Hotel DE',
                        'en' => 'Hotel EN',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $compiler->process($this->container->reveal());
    }

    public function testWithAreas()
    {
        $compiler = new SnippetAreaCompilerPass();

        $structureMetaData1 = $this->createStructureMetaData(
            'test',
            [
                'article' => [
                    'key' => 'article',
                    'title' => [
                        'de' => 'Artikel Test',
                        'en' => 'Article Test',
                    ],
                ],
            ]
        );
        $structureMetaData2 = $this->createStructureMetaData('hotel');

        $this->structureFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $this->structureFactory->getStructures('snippet')->willReturn([
            $structureMetaData1->reveal(),
            $structureMetaData2->reveal(),
        ]);

        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->container->get('sulu_content.structure.factory')->willReturn(
            $this->structureFactory->reveal()
        );
        $this->container->getParameter('sulu_core.locales')->willReturn(['en', 'de']);

        $this->container->setParameter(
            'sulu_snippet.areas',
            [
                'article' => [
                    'key' => 'article',
                    'template' => 'test',
                    'title' => [
                        'de' => 'Artikel Test',
                        'en' => 'Article Test',
                    ],
                ],
            ]
        )->shouldBeCalled();

        $compiler->process($this->container->reveal());
    }

    private function createStructureMetaData($name, $areas = [])
    {
        $structureMetaData = $this->prophesize(StructureMetadata::class);
        $structureMetaData->getName()->willReturn($name);
        $structureMetaData->getAreas()->willReturn($areas);
        $structureMetaData->getTitle('de')->willReturn(ucfirst($name) . ' DE');
        $structureMetaData->getTitle('en')->willReturn(ucfirst($name) . ' EN');

        return $structureMetaData;
    }
}
