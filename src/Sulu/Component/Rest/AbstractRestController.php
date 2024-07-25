<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Controller\ControllerTrait;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Abstract Controller for extracting some required rest functionality.
 */
abstract class AbstractRestController
{
    use ControllerTrait;
    use RestControllerTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->setViewHandler($viewHandler);
    }

    protected function getUser()
    {
        if (!$this->tokenStorage) {
            throw new \LogicException('The TokenStorage property was not set via the constructor".');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
