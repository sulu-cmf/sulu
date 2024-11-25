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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableTrait;

class RoutableTraitTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param T $contentRichEntity
     */
    protected function getRoutableInstance(ContentRichEntityInterface $contentRichEntity): RoutableInterface
    {
        return new class($contentRichEntity) implements RoutableInterface {
            use RoutableTrait;

            /**
             * @var T
             */
            private $resource;

            /**
             * @param T $resource
             */
            public function __construct(ContentRichEntityInterface $resource)
            {
                $this->resource = $resource;
            }

            public static function getResourceKey(): string
            {
                throw new \RuntimeException('Should not be called while executing tests.');
            }

            public function getLocale(): string
            {
                return 'en';
            }

            /**
             * @return T
             */
            public function getResource(): ContentRichEntityInterface
            {
                return $this->resource;
            }
        };
    }

    public function testGetLocale(): void
    {
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $model = $this->getRoutableInstance($contentRichEntity->reveal());
        $this->assertSame('en', $model->getLocale());
    }

    public function testGetResourceId(): void
    {
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentRichEntity->getId()->willReturn('content-id-123');

        $model = $this->getRoutableInstance($contentRichEntity->reveal());
        $this->assertSame('content-id-123', $model->getResourceId());
    }
}
