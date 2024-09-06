<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

/**
 * @RouteResource("custom-urls")
 */
class CustomUrlController extends AbstractRestController implements SecuredControllerInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private CustomUrlManagerInterface $customUrlManager,
        private RequestStack $requestStack,
        private CustomUrlRepositoryInterface $customUrlRepository,
    ) {
        parent::__construct($viewHandler);
    }

    public function cgetAction(string $webspace, Request $request): Response
    {
        $result = $this->customUrlManager->findByWebspaceKey($webspace);

        $list = new CollectionRepresentation($result, CustomUrl::RESOURCE_KEY);

        return $this->handleView($this->view($list));
    }

    public function getAction(string $webspace, int $id, Request $request): Response
    {
        $customUrl = $this->customUrlRepository->find($id);

        return $this->handleView($this->view($customUrl));
    }

    public function postAction(string $webspace, Request $request): Response
    {
        // throw helpful error message if targetLocale is not set
        $this->getRequestParameter($request, 'targetLocale', true);

        $customUrl = $this->customUrlManager->create($webspace, $request->request->all());

        return $this->handleView($this->view($customUrl));
    }

    public function putAction(string $webspace, int $id, Request $request): Response
    {
        $requestData = $request->request->all();
        unset($requestData['creator'], $requestData['changer'], $requestData['created'], $requestData['updated']);

        $customUrl = $this->customUrlRepository->find($id);
        if (null === $customUrl) {
            return $this->handleView($this->view($customUrl, Response::HTTP_NOT_FOUND));
        }

        $this->customUrlManager->save($customUrl, $requestData);

        return $this->handleView($this->view($customUrl));
    }

    public function deleteAction(string $webspace, int $id): Response
    {
        $this->customUrlManager->deleteByIds([$id]);

        return $this->handleView($this->view());
    }

    public function cdeleteAction(string $webspace, Request $request): Response
    {
        $ids = [];
        foreach (\explode(',', $request->attributes->getString('ids', '')) as $id) {
            if ('' === $id) {
                continue;
            }
            $ids[] = (int) $id;
        }

        $this->customUrlManager->deleteByIds($ids);

        return $this->handleView($this->view());
    }

    public function getSecurityContext(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'Unable to get from request stack');

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->attributes->getString('webspace'));
    }

    public function getLocale(Request $request): ?string
    {
        return null;
    }
}
