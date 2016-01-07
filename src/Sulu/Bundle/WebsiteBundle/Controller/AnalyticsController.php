<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\RouteAwareRepresentation;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides webspace analytic rest-endpoint.
 */
class AnalyticsController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    const RESULT_KEY = 'analytics';

    /**
     * Returns webspace analytics by webspace key.
     *
     * @param Request $request
     * @param string $webspaceKey
     *
     * @return Response
     */
    public function cgetAction(Request $request, $webspaceKey)
    {
        $entities = $this->get('sulu_website.analytics.manager')->findAll($webspaceKey);

        $list = new RouteAwareRepresentation(
            new CollectionRepresentation($entities, self::RESULT_KEY),
            'cget_webspace_analytics',
            array_merge($request->request->all(), ['webspaceKey' => $webspaceKey])
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a single analytics key by id.
     *
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function getAction($webspaceKey, $id)
    {
        $entity = $this->get('sulu_website.analytics.manager')->find($id);

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Creates a analytics key for given webspace.
     *
     * @param Request $request
     * @param string $webspaceKey
     *
     * @return Response
     */
    public function postAction(Request $request, $webspaceKey)
    {
        $entity = $this->get('sulu_website.analytics.manager')->create($webspaceKey, $request->request->all());

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Updates analytics key with given id.
     *
     * @param Request $request
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $webspaceKey, $id)
    {
        $entity = $this->get('sulu_website.analytics.manager')->update($id, $request->request->all());

        return $this->handleView($this->view($entity, 200));
    }

    /**
     * Removes given analytics key.
     *
     * @param string $webspaceKey
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($webspaceKey, $id)
    {
        $this->get('sulu_website.analytic s.manager')->remove($id);

        return $this->handleView($this->view(null, 204));
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $webspaceKey = $request->get('webspaceKey');

        return ContentAdmin::SECURITY_CONTEXT_PREFIX . $webspaceKey;
    }
}
