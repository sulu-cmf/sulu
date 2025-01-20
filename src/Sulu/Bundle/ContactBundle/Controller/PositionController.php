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
use Sulu\Bundle\ContactBundle\Domain\Event\ContactPositionCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactPositionModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactPositionRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\PositionRepository;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("contact-position")
 */
class PositionController extends AbstractRestController implements ClassResourceInterface
{
    protected static $entityName = Position::class;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private PositionRepository $positionRepository,
        private EntityManagerInterface $entityManager,
        private DomainEventCollectorInterface $domainEventCollector
    ) {
        parent::__construct($viewHandler);
    }

    /**
     * Shows a single position for the given id.
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
                return $this->positionRepository->find($id);
            }
        );

        return $this->handleView($view);
    }

    /**
     * lists all positions
     * optional parameter 'flat' calls listAction.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $filter = [];
        $ids = $request->get('ids');

        if ($ids) {
            $filter['id'] = \explode(',', $ids);
        }

        $list = new CollectionRepresentation(
            $this->positionRepository->findBy($filter, ['position' => 'ASC']),
            Position::RESOURCE_KEY
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Creates a new position.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('position');

        try {
            if (null == $name) {
                throw new RestException(
                    'There is no position-name for the given name'
                );
            }

            $position = new Position();
            $position->setPosition($name);

            $this->entityManager->persist($position);

            $this->domainEventCollector->collect(
                new ContactPositionCreatedEvent($position, $request->request->all())
            );

            $this->entityManager->flush();

            $view = $this->view($position, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing position for the given id.
     *
     * @param int $id The id of the position to update
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            /** @var Position $position */
            $position = $this->positionRepository->find($id);

            if (!$position) {
                throw new EntityNotFoundException(self::$entityName, $id);
            } else {
                $name = $request->get('position');

                if (empty($name)) {
                    throw new RestException('There is no position-name for the given name');
                } else {
                    $position->setPosition($name);

                    $this->domainEventCollector->collect(
                        new ContactPositionModifiedEvent($position, $request->request->all())
                    );

                    $this->entityManager->flush();
                    $view = $this->view($position, 200);
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
                /* @var Position $position */
                $position = $this->positionRepository->find($id);

                if (!$position) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $positionId = $position->getId();
                $positionName = $position->getPosition();

                $this->entityManager->remove($position);

                $this->domainEventCollector->collect(
                    new ContactPositionRemovedEvent($positionId, $positionName)
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
     * Delete a position for the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        try {
            $delete = function($id) {
                /* @var Position $position */
                $position = $this->positionRepository->find($id);

                if (!$position) {
                    throw new EntityNotFoundException(self::$entityName, $id);
                }

                $positionId = $position->getId();
                $positionName = $position->getPosition();

                $this->entityManager->remove($position);

                $this->domainEventCollector->collect(
                    new ContactPositionRemovedEvent($positionId, $positionName)
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
     * Add or update a bunch of positions.
     *
     * @return Response
     */
    public function cpatchAction(Request $request)
    {
        try {
            $data = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (!isset($item['position'])) {
                    throw new RestException(
                        'There is no position-name for the given name'
                    );
                }

                $data[] = $this->addAndUpdatePositions($item);
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
     * @return Position added or updated entity
     *
     * @throws EntityNotFoundException
     */
    private function addAndUpdatePositions($item)
    {
        if (isset($item['id']) && !empty($item['id'])) {
            /* @var Position $position */
            $position = $this->positionRepository->find($item['id']);

            if (null == $position) {
                throw new EntityNotFoundException(self::$entityName, $item['id']);
            } else {
                $position->setPosition($item['position']);

                $this->domainEventCollector->collect(
                    new ContactPositionModifiedEvent($position, $item)
                );
            }
        } else {
            $position = new Position();
            $position->setPosition($item['position']);
            $this->entityManager->persist($position);

            $this->domainEventCollector->collect(
                new ContactPositionCreatedEvent($position, $item)
            );
        }

        return $position;
    }
}
