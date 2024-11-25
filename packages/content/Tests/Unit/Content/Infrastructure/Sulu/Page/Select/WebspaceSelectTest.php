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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\Page\Select;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Page\Select\WebspaceSelect;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceSelectTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    private function createWebspaceSelectInstance(): WebspaceSelect
    {
        return new WebspaceSelect($this->webspaceManager->reveal());
    }

    public function testGetValues(): void
    {
        $webspaceA = new Webspace();
        $webspaceA->setKey('webspace-a');
        $webspaceA->setName('Webspace A');
        $webspaceB = new Webspace();
        $webspaceB->setKey('webspace-b');
        $webspaceB->setName('Webspace B');
        $webspaceCollection = new WebspaceCollection([
            'webspace-a' => $webspaceA,
            'webspace-b' => $webspaceB,
        ]);

        $this->webspaceManager->getWebspaceCollection()
            ->shouldBeCalled()
            ->willReturn($webspaceCollection);

        $webspaceSelect = $this->createWebspaceSelectInstance();

        $this->assertSame(
            [
                [
                    'name' => 'webspace-a',
                    'title' => 'Webspace A',
                ],
                [
                    'name' => 'webspace-b',
                    'title' => 'Webspace B',
                ],
            ],
            $webspaceSelect->getValues()
        );
    }
}
