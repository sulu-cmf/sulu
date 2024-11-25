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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\WebspaceDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceDataMapperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspaceCollection = new WebspaceCollection();
        $this->webspaceManager->getWebspaceCollection()
            ->willReturn($this->webspaceCollection);
    }

    protected function createWebspaceDataMapperInstance(): WebspaceDataMapper
    {
        return new WebspaceDataMapper($this->webspaceManager->reveal());
    }

    public function testMapNoWebspaceInterface(): void
    {
        $data = [
            'author' => 1,
            'authored' => '2020-05-08T00:00:00+00:00',
        ];

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $authorMapper = $this->createWebspaceDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent->reveal(), $localizedDimensionContent->reveal(), $data);
        $this->assertTrue(true); // Avoid risky test as this is an early return test
    }

    public function testMapWebspaceNoData(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $authorMapper = $this->createWebspaceDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getMainWebspace());
    }

    public function testMapDefaultWebspace(): void
    {
        $data = [];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $webspace = new Webspace();
        $webspace->setKey('default-webspace');
        $this->webspaceCollection->setWebspaces(['default-webspace' => $webspace]);

        $authorMapper = $this->createWebspaceDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('default-webspace', $localizedDimensionContent->getMainWebspace());
    }

    public function testMapData(): void
    {
        $data = [
            'mainWebspace' => 'example',
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);

        $authorMapper = $this->createWebspaceDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame('example', $localizedDimensionContent->getMainWebspace());
    }

    public function testMapDataEmpty(): void
    {
        $data = [
            'mainWebspace' => null,
        ];

        $example = new Example();
        $unlocalizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent = new ExampleDimensionContent($example);
        $localizedDimensionContent->setMainWebspace('example');

        $authorMapper = $this->createWebspaceDataMapperInstance();
        $authorMapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getMainWebspace());
    }
}
