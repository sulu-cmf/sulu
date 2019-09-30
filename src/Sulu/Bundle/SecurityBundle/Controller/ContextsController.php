<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

class ContextsController extends RestController implements ClassResourceInterface
{
    /**
     * @var AdminPool
     */
    private $adminPool;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        AdminPool $adminPool
    ) {
        parent::__construct($viewHandler);

        $this->adminPool = $adminPool;
    }

    public function cgetAction(Request $request)
    {
        $securityContexts = $this->adminPool->getSecurityContextsWithPlaceholder();
        $view = $this->view($securityContexts);

        return $this->handleView($view);
    }
}
