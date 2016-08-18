<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Teaser;

use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Bundle\ContentBundle\Teaser\Teaser;
use Sulu\Bundle\ContentBundle\Teaser\TeaserManager;
use Sulu\Bundle\ContentBundle\Teaser\TeaserManagerInterface;

class TeaserManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TeaserProviderPoolInterface
     */
    private $providerPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    public function setUp()
    {
        $this->providerPool = $this->prophesize(TeaserProviderPoolInterface::class);

        $this->teaserManager = new TeaserManager($this->providerPool->reveal());
    }

    public function testFind()
    {
        $items = [
            ['type' => 'content', 'id' => '123-123-123'],
            ['type' => 'media', 'id' => 1],
            ['type' => 'content', 'id' => '312-312-312'],
        ];
        $teasers = $this->getTeaserMocks($items);

        $contentProvider = $this->prophesize(TeaserProviderInterface::class);
        $contentProvider->find(['123-123-123', '312-312-312'], 'de')->willReturn([$teasers[2], $teasers[0]]);
        $mediaProvider = $this->prophesize(TeaserProviderInterface::class);
        $mediaProvider->find([1], 'de')->willReturn([$teasers[1]]);

        $this->providerPool->getProvider('content')->shouldBeCalledTimes(1)->willReturn($contentProvider->reveal());
        $this->providerPool->getProvider('media')->shouldBeCalledTimes(1)->willReturn($mediaProvider->reveal());

        $this->assertEquals($teasers, $this->teaserManager->find($items, 'de'));
    }

    private function getTeaserMocks(array $items)
    {
        return array_map(
            function ($item) {
                $teaser = $this->prophesize(Teaser::class);
                $teaser->getType()->willReturn($item['type']);
                $teaser->getId()->willReturn($item['id']);

                return $teaser->reveal();
            },
            $items
        );
    }
}
