<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Exception\MetadataNotFoundException;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BaseMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $dispatcher;

    /**
     * @var BaseMetadataFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->dispatcher
            ->dispatch(Argument::any(), Argument::any())
            ->willReturnArgument(0);
        $this->factory = new BaseMetadataFactory(
            $this->dispatcher->reveal(),
            [
                [
                    'alias' => 'page',
                    'class' => 'Class\Page',
                    'phpcr_type' => 'sulu:page',
                ],
                [
                    'alias' => 'snippet',
                    'class' => 'Class\Snippet',
                    'phpcr_type' => 'sulu:snippet',
                ],
            ]
        );
    }

    /**
     * It should retrieve metadata for a fully qualified class name.
     */
    public function testGetForClass(): void
    {
        $metadata = $this->factory->getMetadataForClass('Class\Page');
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals('page', $metadata->getAlias());
        $this->assertEquals('Class\Page', $metadata->getClass());
        $this->assertEquals('sulu:page', $metadata->getPhpcrType());
    }

    /**
     * It should throw an exception if there is no mapping for the class name.
     */
    public function testGetForClassNotFound(): void
    {
        $this->expectException(MetadataNotFoundException::class);
        $this->factory->getMetadataForClass('Class\Page\NotFound');
    }

    /**
     * It should retrieve metadata for a given alias.
     */
    public function testGetForAlias(): void
    {
        $metadata = $this->factory->getMetadataForAlias('snippet');
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals('snippet', $metadata->getAlias());
        $this->assertEquals('Class\Snippet', $metadata->getClass());
        $this->assertEquals('sulu:snippet', $metadata->getPhpcrType());
    }

    /**
     * It should throw an exception if there is no mapping for given alias.
     */
    public function testGetForAliasNotFound(): void
    {
        $this->expectException(MetadataNotFoundException::class);
        $this->factory->getMetadataForAlias('yak');
    }

    /**
     * It should retrieve metadata for a given phpcrType.
     */
    public function testGetForPhpcrType(): void
    {
        $metadata = $this->factory->getMetadataForPhpcrType('sulu:snippet');
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals('snippet', $metadata->getAlias());
        $this->assertEquals('Class\Snippet', $metadata->getClass());
        $this->assertEquals('sulu:snippet', $metadata->getPhpcrType());
    }

    public function testHasPhpcrType(): void
    {
        $res = $this->factory->hasMetadataForPhpcrType('sulu:snippet');
        $this->assertTrue($res);

        $res = $this->factory->hasMetadataForPhpcrType('foobar');
        $this->assertFalse($res);
    }

    /**
     * It should say if it has metadata for a class.
     */
    public function testHasMetadataForClass(): void
    {
        $this->assertTrue($this->factory->hasMetadataForClass('Class\Snippet'));
    }

    /**
     * It should say if it has metadata for a class.
     */
    public function testHasMetadataForProxyClass(): void
    {
        $this->assertTrue($this->factory->hasMetadataForClass('ProxyManagerGeneratedProxy\__PM__\Class\Snippet\Generateda84aebfffbf882fd8bddc950faa89e05'));
    }

    /**
     * It should throw an exception if there is no mapping for given phpcrType.
     */
    public function testGetForPhpcrTypeNotFound(): void
    {
        $this->expectException(MetadataNotFoundException::class);
        $this->factory->getMetadataForPhpcrType('yak');
    }

    /**
     * It has a method to determine if an alias exists.
     */
    public function testHasAlias(): void
    {
        $this->assertTrue($this->factory->hasAlias('page'));
        $this->assertFalse($this->factory->hasAlias('fooabarbardg'));
    }

    /**
     * It should return metadata for all mapped documents.
     */
    public function testAllMetadata(): void
    {
        $metadatas = $this->factory->getAllMetadata();
        $this->assertCount(2, $metadatas);
        $this->assertContainsOnlyInstancesOf(Metadata::class, $metadatas);
        $metadata = \reset($metadatas);
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertEquals('Class\Page', $metadata->getClass());
    }
}
