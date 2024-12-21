<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactTitleCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactTitleModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactTitleRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("contact-title")
 */
class ContactTitleController extends AbstractRestController implements ClassResourceInterface
{
    protected static $entityName = ContactTitle::class;

    /**
     * @var string
     *
     * @deprecated
     *
     * @see ContactTitle::RESOURCE_KEY
     */
    protected static $entityKey = ContactTitle::RESOURCE_KEY;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private ContactTitleRepository $contactTitleRepository,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Shows a single contact title for the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function($id) {
                return $this->contactTitleRepository->find($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all contact titles
     * optional parameter 'flat' calls listAction.
     *
     * @return Response
     */
    public function cgetAction()
    {
        $list = new CollectionRepresentation(
            $this->contactTitleRepository->findBy([], ['title' => 'ASC']),
            ContactTitle::RESOURCE_KEY
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new contact title.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('title');

        try {
            if (null == $name) {
                throw new RestException(
                    'There is no title-name for the given title'
                );
            }

            $title = new ContactTitle();
            $title->setTitle($name);

            $this->entityManager->persist($title);

            $this->domainEventCollector->collect(
                new ContactTitleCreatedEvent($title, $request->request->all())
            );

            $this->entityManager->flush();

            $view = $this->view($title, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing contact title for the given id.
     *
     * @param int $id The id of the title to update
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            /** @var ContactTitle $title */
            $title = $this->contactTitleRepository->find($id);

            if (!$title) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $name = $request->get('title');

                if (empty($name)) {
                    throw new RestException('There is no title-name for the given title');
                } else {
                    $title->setTitle($name);

                    $this->domainEventCollector->collect(
                        new ContactTitleModifiedEvent($title, $request->request->all())
                    );

                    $this->entityManager->flush();
                    $view = $this->view($title, 200);
                }
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    public function cdeleteAction(Request $request)
    {
        $ids = \array_filter(\explode(',', $request->get('ids', '')));

        try {
            foreach ($ids as $id) {
                /* @var ContactTitle $title */
                $title = $this->contactTitleRepository->find($id);

                if (!$title) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $titleId = $title->getId();
                $titleName = $title->getTitle();

                $this->entityManager->remove($title);

                $this->domainEventCollector->collect(
                    new ContactTitleRemovedEvent($titleId, $titleName)
                );
            }

            $this->entityManager->flush();

            $view = $this->view();
        } catch (EntityNotFoundException $e) {
            $view = $this->view($e->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Delete a contact title for the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        try {
            $delete = function($id) {
                /* @var ContactTitle $title */
                $title = $this->contactTitleRepository->find($id);

                if (!$title) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $titleId = $title->getId();
                $titleName = $title->getTitle();

                $this->entityManager->remove($title);

                $this->domainEventCollector->collect(
                    new ContactTitleRemovedEvent($titleId, $titleName)
                );

                $this->entityManager->flush();
            };

            $view = $this->responseDelete($id, $delete);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Add or update a bunch of contact titles.
     *
     * @return Response
     */
    public function cpatchAction(Request $request)
    {
        try {
            $data = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (!isset($item['title'])) {
                    throw new RestException(
                        'There is no title-name for the given title'
                    );
                }

                $data[] = $this->addAndUpdateTitles($item);
                ++$i;
            }

            $this->entityManager->flush();
            $view = $this->view($data, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Helper function for patch action.
     *
     * @param mixed[] $item
     *
     * @return ContactTitle added or updated entity
     *
     * @throws EntityNotFoundException
     */
    private function addAndUpdateTitles($item)
    {
        if (isset($item['id']) && !empty($item['id'])) {
            /* @var ContactTitle $title */
            $title = $this->contactTitleRepository->find($item['id']);

            if (null == $title) {
                throw new EntityNotFoundException(self::$entityName, $item['id']);
            } else {
                $title->setTitle($item['title']);

                $this->domainEventCollector->collect(
                    new ContactTitleModifiedEvent($title, $item)
                );
            }
        } else {
            $title = new ContactTitle();
            $title->setTitle($item['title']);
            $this->entityManager->persist($title);

            $this->domainEventCollector->collect(
                new ContactTitleCreatedEvent($title, $item)
            );
        }

        return $title;
    }
}
