<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Teaser\Provider;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\ProviderNotFoundException;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPool;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface;

class TeaserProviderPoolTest extends TestCase
{
    /**
     * @var TeaserProviderInterface[]
     */
    private $providers;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    public function setUp(): void
    {
        $this->providers = [
            'content' => $this->prophesize(TeaserProviderInterface::class),
            'media' => $this->prophesize(TeaserProviderInterface::class),
        ];

        $this->teaserProviderPool = new TeaserProviderPool(
            array_map(
                function($provider) {
                    return $provider->reveal();
                },
                $this->providers
            )
        );
    }

    public function testGetProvider()
    {
        $this->assertEquals($this->providers['content']->reveal(), $this->teaserProviderPool->getProvider('content'));
    }

    public function testGetProviderNotFound()
    {
        $this->expectException(ProviderNotFoundException::class);

        $this->teaserProviderPool->getProvider('test');
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->teaserProviderPool->hasProvider('content'));
    }

    public function testHasProviderNotFound()
    {
        $this->assertFalse($this->teaserProviderPool->hasProvider('test'));
    }

    public function testGetConfiguration()
    {
        $configuration = [
            'content' => new TeaserConfiguration('Pages', 'pages', 'column_list', ['title'], 'Choose'),
            'media' => new TeaserConfiguration('Media', 'media', 'masonry', ['title', 'version'], 'Choose'),
        ];

        $this->providers['content']->getConfiguration()->willReturn($configuration['content']);
        $this->providers['media']->getConfiguration()->willReturn($configuration['media']);

        $this->assertEquals($configuration, $this->teaserProviderPool->getConfiguration());
    }
}
