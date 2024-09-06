<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\CustomUrl\Routing\CustomUrlDefaultsProvider;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class CustomUrlDefaultsProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private ObjectProphecy $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private ObjectProphecy $documentInspector;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private ObjectProphecy $structureManager;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private ObjectProphecy $webspaceManager;

    private CustomUrlDefaultsProvider $customUrlDefaultsProvider;

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->customUrlDefaultsProvider = new CustomUrlDefaultsProvider();
    }

    public function testStuff(): void
    {
        $this->fail('We need to test this class');
    }
}
