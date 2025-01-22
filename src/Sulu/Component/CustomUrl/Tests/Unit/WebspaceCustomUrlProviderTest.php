<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\CustomUrl\WebspaceCustomUrlProvider;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceCustomUrlProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetUrls(): void
    {
        $customUrlManager = $this->prophesize(CustomUrlManagerInterface::class);
        $provider = new WebspaceCustomUrlProvider($customUrlManager->reveal());

        $customUrlManager->findUrls('sulu_io')->willReturn(['1.sulu.lo', '1.sulu.lo/2']);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $result = $provider->getUrls($webspace->reveal(), 'prod');

        $this->assertEquals([new Url('1.sulu.lo'), new Url('1.sulu.lo/2')], $result);
    }
}
