<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Infrastructure\Sulu\Admin\View;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View\ReferenceViewBuilderFactory;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class ReferenceViewBuilderFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private ObjectProphecy $securityChecker;

    private ReferenceViewBuilderFactory $referenceViewBuilderFactory;

    public function setUp(): void
    {
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);

        $this->referenceViewBuilderFactory = new ReferenceViewBuilderFactory(
            new ViewBuilderFactory(),
            $this->securityChecker->reveal(),
        );
    }

    public function testCreateReferenceListViewBuilder(): void
    {
        $listViewBuilder = $this->referenceViewBuilderFactory->createReferenceListViewBuilder(
            'sulu_media.form.references',
            '/references',
            'media',
        );

        $view = $listViewBuilder->getView();

        $this->assertSame(
            'sulu_media.form.references',
            $view->getName()
        );

        $this->assertSame(
            'sulu_admin.list',
            $view->getType()
        );

        $this->assertSame(
            'references',
            $view->getOption('resourceKey')
        );

        $this->assertSame(
            ['resourceKey' => 'media'],
            $view->getOption('requestParameters')
        );

        $this->assertSame(
            ['id' => 'resourceId'],
            $view->getOption('routerAttributesToListRequest')
        );
    }

    public function testHasReferenceListPermission(): void
    {
        $this->securityChecker->hasPermission(ReferenceAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW)
            ->willReturn(true);

        $this->assertTrue($this->referenceViewBuilderFactory->hasReferenceListPermission());
    }
}
