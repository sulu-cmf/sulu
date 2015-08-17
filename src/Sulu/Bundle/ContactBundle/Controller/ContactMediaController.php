<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContactMediaController.
 *
 * @RouteResource("Medias")
 */
class ContactMediaController extends AbstractMediaController implements ClassResourceInterface
{
    /**
     * Removes a media from the relation to the account.
     *
     * @param $id - contact id
     * @param $slug - media id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, $slug)
    {
        return $this->removeMediaFromEntity(
            $this->container->getParameter('sulu.model.contact.class'),
            $id,
            $slug
        );
    }

    /**
     * Adds a new media to the account.
     *
     * @param $id
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction($id, Request $request)
    {
        return $this->addMediaToEntity(
            $this->container->getParameter('sulu.model.contact.class'),
            $id,
            $request->get('mediaId', '')
        );
    }

    /**
     * Lists all media of an account
     * optional parameter 'flat' calls listAction.
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction($id, Request $request)
    {
        return $this->getMultipleView(
            $this->getContactEntityName(),
            'get_contact_medias',
            $this->get('sulu_contact.contact_manager'),
            $id,
            $request
        );
    }

    /**
     * Returns all fields that can be used by list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction()
    {
        return $this->getFieldsView($this->getContactEntityName());
    }

    private function getContactEntityName()
    {
        return $this->container->getParameter('sulu_contact.contact.entity');
    }
}
