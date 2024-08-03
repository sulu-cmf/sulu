<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\SchemaMetadata;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\NotFoundExceptionInterface;
use Sulu\Bundle\AdminBundle\Exception\PropertyMetadataMapperNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

class PropertyMetadataMapperRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ServiceLocator>
     */
    private $serviceLocator;

    /**
     * @var PropertyMetadataMapperRegistry
     */
    private $propertyMetadataMapperRegistry;

    protected function setUp(): void
    {
        $this->serviceLocator = $this->prophesize(ServiceLocator::class);

        $this->propertyMetadataMapperRegistry = new PropertyMetadataMapperRegistry(
            $this->serviceLocator->reveal()
        );
    }

    public function testHas(): void
    {
        $this->serviceLocator->has('mapper')->willReturn(true);

        $this->assertTrue($this->propertyMetadataMapperRegistry->has('mapper'));
    }

    public function testHasNot(): void
    {
        $this->serviceLocator->has('mapper')->willReturn(false);

        $this->assertFalse($this->propertyMetadataMapperRegistry->has('mapper'));
    }

    public function testGet(): void
    {
        $propertyMetadataMapper = $this->prophesize(PropertyMetadataMapperInterface::class);

        $this->serviceLocator->get('mapper')->willReturn($propertyMetadataMapper->reveal());

        $this->assertSame($propertyMetadataMapper->reveal(), $this->propertyMetadataMapperRegistry->get('mapper'));
    }

    public function testGetNotFound(): void
    {
        $this->serviceLocator->get('mapper')->willThrow($this->createNotFoundException());

        $this->expectException(PropertyMetadataMapperNotFoundException::class);

        $this->propertyMetadataMapperRegistry->get('mapper');
    }

    public function testGetOtherException(): void
    {
        $exception = new \Exception();

        $this->serviceLocator->get('mapper')->willThrow($exception);

        $this->expectExceptionObject($exception);

        $this->propertyMetadataMapperRegistry->get('mapper');
    }

    private function createNotFoundException(): NotFoundExceptionInterface
    {
        return new class extends \InvalidArgumentException implements NotFoundExceptionInterface {
        };
    }
}
