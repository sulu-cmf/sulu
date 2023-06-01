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
use Sulu\Bundle\ContactBundle\Contact\AbstractContactManager;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaAddedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactMediaRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\ListBuilderFactory\MediaListBuilderFactory;
use Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory\MediaListRepresentationFactory;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class ContactMediaController.
 *
 * @RouteResource("Medias")
 */
class ContactMediaController extends AbstractMediaController implements ClassResourceInterface
{
    protected static $mediaEntityKey = 'contact_media';

    /**
     * @var AbstractContactManager
     */
    private $contactManager;

    /**
     * @var string
     */
    private $contactClass;

    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TokenStorageInterface $tokenStorage,
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        EntityManagerInterface $entityManager,
        MediaRepositoryInterface $mediaRepository,
        MediaManagerInterface $mediaManager,
        AbstractContactManager $contactManager,
        DomainEventCollectorInterface $domainEventCollector,
        string $contactClass,
        string $mediaClass,
        ?MediaListBuilderFactory $mediaListBuilderFactory = null,
        ?MediaListRepresentationFactory $mediaListRepresentationFactory = null,
        ?FieldDescriptorFactoryInterface $fieldDescriptorFactory = null
    ) {
        parent::__construct(
            $viewHandler,
            $tokenStorage,
            $restHelper,
            $listBuilderFactory,
            $entityManager,
            $mediaRepository,
            $mediaManager,
            $mediaClass,
            $mediaListBuilderFactory,
            $mediaListRepresentationFactory,
            $fieldDescriptorFactory
        );

        $this->contactManager = $contactManager;
        $this->contactClass = $contactClass;
        $this->domainEventCollector = $domainEventCollector;
    }

    public function deleteAction(int $contactId, int $id)
    {
        $dispatchDomainEventCallback = function(ContactInterface $contact, MediaInterface $media) {
            $this->domainEventCollector->collect(
                new ContactMediaRemovedEvent($contact, $media)
            );
        };

        return $this->removeMediaFromEntity($this->contactClass, $contactId, $id, $dispatchDomainEventCallback);
    }

    public function postAction(int $contactId, Request $request)
    {
        $mediaId = $request->get('mediaId', '');

        $dispatchDomainEventCallback = function(ContactInterface $contact, MediaInterface $media) {
            $this->domainEventCollector->collect(
                new ContactMediaAddedEvent($contact, $media)
            );
        };

        return $this->addMediaToEntity($this->contactClass, $contactId, $mediaId, $dispatchDomainEventCallback);
    }

    public function cgetAction(int $contactId, Request $request)
    {
        return $this->getMultipleView(
            $this->contactClass,
            'sulu_contact.get_contact_medias',
            $this->contactManager,
            $contactId,
            $request
        );
    }
}
