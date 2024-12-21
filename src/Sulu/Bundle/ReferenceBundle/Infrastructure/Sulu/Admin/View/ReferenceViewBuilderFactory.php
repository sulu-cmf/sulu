<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\View;

use Sulu\Bundle\AdminBundle\Admin\View\ListItemAction;
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\Admin\ReferenceAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * @final
 */
class ReferenceViewBuilderFactory implements ReferenceViewBuilderFactoryInterface
{
    /**
     * @var ViewBuilderFactoryInterface
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        ViewBuilderFactoryInterface $viewBuilderFactory,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->viewBuilderFactory = $viewBuilderFactory;
        $this->securityChecker = $securityChecker;
    }

    public function createReferenceListViewBuilder(
        string $name,
        string $path,
        string $resourceKey,
        string $resourceIdRouterAttribute = 'id'
    ): ListViewBuilderInterface {
        return $this->viewBuilderFactory
            ->createListViewBuilder($name, $path)
            ->setResourceKey(ReferenceInterface::RESOURCE_KEY)
            ->setListKey(ReferenceInterface::LIST_KEY)
            ->setTabTitle('sulu_reference.references')
            ->addListAdapters(['tree_table'])
            ->disableSelection()
            ->addRouterAttributesToListRequest([$resourceIdRouterAttribute => 'resourceId'])
            ->addItemActions([
                new ListItemAction(
                    'detail_link',
                    [
                        'resource_key_property' => 'referenceResourceKey',
                        'resource_id_property' => 'referenceResourceId',
                        'resource_view_attributes_property' => 'referenceRouterAttributes',
                    ],
                ),
            ])
            ->addRequestParameters(['resourceKey' => $resourceKey]);
    }

    public function hasReferenceListPermission(): bool
    {
        return $this->securityChecker->hasPermission(ReferenceAdmin::SECURITY_CONTEXT, PermissionTypes::VIEW);
    }
}
