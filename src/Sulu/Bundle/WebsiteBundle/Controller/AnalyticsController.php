<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManagerInterface;
use Sulu\Bundle\WebsiteBundle\Cache\CacheClearerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides webspace analytics rest-endpoint.
 */
class AnalyticsController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * @var AnalyticsManagerInterface
     */
    private $analyticsManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CacheClearerInterface
     */
    private $cacheClearer;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        AnalyticsManagerInterface $analyticsManager,
        EntityManagerInterface $entityManager,
        CacheClearerInterface $cacheClearer,
        RequestStack $requestStack
    ) {
        parent::__construct($viewHandler);
        $this->analyticsManager = $analyticsManager;
        $this->entityManager = $entityManager;
        $this->cacheClearer = $cacheClearer;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns webspace analytics by webspace key.
     *
     * @param string $webspace
     *
     * @return Response
     */
    public function cgetAction(Request $request, $webspace)
    {
        $entities = $this->analyticsManager->findAll($webspace);

        $list = new CollectionRepresentation($entities, AnalyticsInterface::RESOURCE_KEY);

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a single analytics by id.
     *
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function getAction($webspace, $id)
    {
        $entity = $this->analyticsManager->find($id);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Creates a analytics for given webspace.
     *
     * @param string $webspace
     *
     * @return Response
     */
    public function postAction(Request $request, $webspace)
    {
        $data = $request->request->all();
        $data['content'] = $this->buildContent($data);

        $entity = $this->analyticsManager->create($webspace, $data);
        $this->entityManager->flush();
        $this->cacheClearer->clear([WebspaceReferenceStore::generateTagByWebspaceKey($webspace)]);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Updates analytics with given id.
     *
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $webspace, $id)
    {
        $data = $request->request->all();
        $data['content'] = $this->buildContent($data);

        $entity = $this->analyticsManager->update($id, $data);
        $this->entityManager->flush();
        $this->cacheClearer->clear([WebspaceReferenceStore::generateTagByWebspaceKey($webspace)]);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Removes given analytics.
     *
     * @param string $webspace
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($webspace, $id)
    {
        $this->analyticsManager->remove($id);
        $this->entityManager->flush();
        $this->cacheClearer->clear([WebspaceReferenceStore::generateTagByWebspaceKey($webspace)]);

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Removes a list of analytics.
     *
     * @return Response
     */
    public function cdeleteAction(Request $request, $webspace)
    {
        $ids = \array_filter(\explode(',', $request->get('ids', '')));

        $this->analyticsManager->removeMultiple($ids);
        $this->entityManager->flush();
        $this->cacheClearer->clear([WebspaceReferenceStore::generateTagByWebspaceKey($webspace)]);

        return $this->handleView($this->view(null, 204));
    }

    public function getSecurityContext()
    {
        $request = $this->requestStack->getCurrentRequest();

        return WebsiteAdmin::getAnalyticsSecurityContext($request->get('webspace'));
    }

    private function buildContent(array $data)
    {
        if (!\array_key_exists('type', $data)) {
            return null;
        }

        return match ($data['type']) {
            'google' => $data['google_key'] ?? null,
            'google_tag_manager' => $data['google_tag_manager_key'] ?? null,
            'matomo' => [
                'siteId' => $data['matomo_id'] ?? null,
                'url' => $data['matomo_url'] ?? null,
            ],
            'custom' => [
                'position' => $data['custom_position'] ?? null,
                'value' => $data['custom_script'] ?? null,
            ],
            default => null,
        };
    }
}
