<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Content\Types;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\LocationBundle\Content\Types\LocationContentType;
use Sulu\Component\Content\Compat\PropertyInterface;

class LocationContentTypeTest extends TestCase
{
    /**
     * @var NodeInterface
     */
    private $phpcrNode;

    /**
     * @var PropertyInterface
     */
    private $suluProperty;

    /**
     * @var LocationContentType
     */
    protected $locationContent;

    public function setUp(): void
    {
        $this->phpcrNode = $this->getMockBuilder(NodeInterface::class)->getMock();
        $this->suluProperty = $this->getMockBuilder(PropertyInterface::class)->getMock();
        $this->locationContent = new LocationContentType();
    }

    protected function initReadTest($data)
    {
        $this->suluProperty->expects($this->once())
            ->method('setValue')
            ->with($data);
    }

    public static function provideRead()
    {
        return [
            [
                ['foo_bar' => 'bar_foo'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRead')]
    public function testRead($data): void
    {
        $this->initReadTest($data);

        $this->phpcrNode->expects($this->once())
            ->method('getPropertyValueWithDefault')
            ->with('foobar', null)
            ->willReturn(\json_encode($data));

        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->willReturn('foobar');

        $this->locationContent->read(
            $this->phpcrNode,
            $this->suluProperty,
            'webspace_key',
            'fr',
            'segment'
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRead')]
    public function testWrite($data): void
    {
        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->willReturn('myname');

        $this->suluProperty->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->phpcrNode->expects($this->once())
            ->method('setProperty')
            ->with('myname', \json_encode($data));

        $this->locationContent->write(
            $this->phpcrNode,
            $this->suluProperty,
            1,
            'webspace_key',
            'fr',
            'segment'
        );
    }
}
