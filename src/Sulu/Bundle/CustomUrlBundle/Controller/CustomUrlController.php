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

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides rest api for custom-urls.
 *
 * @RouteResource("custom-urls")
 */
class CustomUrlController extends AbstractRestController implements SecuredControllerInterface
{
    use RequestParametersTrait;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private CustomUrlManagerInterface $customUrlManager,
        private DocumentManagerInterface $documentManager,
        private RequestStack $requestStack
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Returns a list of custom-urls.
     *
     * @param string $webspace
     *
     * @return Response
     */
    public function cgetAction($webspace, Request $request)
    {
        $result = $this->customUrlManager->findList($webspace);

        $list = new CollectionRepresentation($result, CustomUrlDocument::RESOURCE_KEY);

        return $this->handleView($this->view($list));
    }

    /**
     * Returns a single custom-url identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     *
     * @return Response
     */
    public function getAction($webspace, $id, Request $request)
    {
        $document = $this->customUrlManager->find($id);

        // FIXME without this target-document will not be loaded (for serialization)
        // - issue https://github.com/sulu-io/sulu-document-manager/issues/71
        if (null !== $document->getTargetDocument()) {
            $document->getTargetDocument()->getTitle();
        }

        $view = $this->view($document);

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * Create a new custom-url object.
     *
     * @param string $webspace
     *
     * @return Response
     */
    public function postAction($webspace, Request $request)
    {
        // throw helpful error message if targetLocale is not set
        $this->getRequestParameter($request, 'targetLocale', true);

        $document = $this->customUrlManager->create(
            $webspace,
            $request->request->all()
        );
        $this->documentManager->flush();

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    /**
     * Update an existing custom-url object identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     *
     * @return Response
     */
    public function putAction($webspace, $id, Request $request)
    {
        $manager = $this->customUrlManager;

        $document = $manager->save($id, $request->request->all());
        $this->documentManager->flush();

        $context = new Context();
        $context->setGroups(['defaultCustomUrl', 'fullRoute']);

        return $this->handleView($this->view($document)->setContext($context));
    }

    /**
     * Delete a single custom-url identified by uuid.
     *
     * @param string $webspace
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction($webspace, $id)
    {
        $manager = $this->customUrlManager;
        $manager->delete($id);
        $this->documentManager->flush();

        return $this->handleView($this->view());
    }

    /**
     * Deletes a list of custom-urls identified by a list of uuids.
     *
     * @param string $webspace
     *
     * @return Response
     */
    public function cdeleteAction($webspace, Request $request)
    {
        $ids = \array_filter(\explode(',', $request->get('ids', '')));

        foreach ($ids as $ids) {
            $this->customUrlManager->delete($ids);
        }
        $this->documentManager->flush();

        return $this->handleView($this->view());
    }

    public function getSecurityContext()
    {
        $request = $this->requestStack->getCurrentRequest();

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->get('webspace'));
    }

    public function getLocale(Request $request)
    {
        return null;
    }
}
