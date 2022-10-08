<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Search\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMetaRepository;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

class PermissionListenerTest extends TestCase
{
    /**
     * @var PermissionListener
     */
    private $permissionListener;

    /**
     * @var ObjectProphecy<FileVersionMetaRepository>
     */
    private $fileVersionMetaRepository;

    /**
     * @var ObjectProphecy<SearchManagerInterface>
     */
    private $searchManager;

    public function setUp(): void
    {
        $this->fileVersionMetaRepository = $this->prophesize(FileVersionMetaRepository::class);
        $this->searchManager = $this->prophesize(SearchManagerInterface::class);

        $this->permissionListener = new PermissionListener(
            $this->fileVersionMetaRepository->reveal(),
            $this->searchManager->reveal()
        );
    }

    public function testOnPermissionUpdate(): void
    {
        $event = new PermissionUpdateEvent(Collection::class, '1', null);
        $document1 = new \stdClass();
        $document2 = new \stdClass();

        $this->fileVersionMetaRepository->findByCollectionId('1')->willReturn([$document1, $document2]);
        $this->searchManager->deindex($document1)->shouldBeCalled();
        $this->searchManager->deindex($document2)->shouldBeCalled();

        $this->permissionListener->onPermissionUpdate($event);
    }

    public function testOnPermissionUpdateWrongType(): void
    {
        $event = new PermissionUpdateEvent(\stdClass::class, '1', null);

        $this->fileVersionMetaRepository->findByCollectionId(Argument::any())->shouldNotBeCalled();
        $this->searchManager->deindex(Argument::any())->shouldNotBeCalled();

        $this->permissionListener->onPermissionUpdate($event);
    }
}
