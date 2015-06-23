<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Search\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\SearchBundle\Search\Document;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Persistence\Model\TimestampableInterface;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add blame (creator, changor) and timestamp (created, changed) to
 * the document before it is indexed.
 *
 * Works for both objects implementing interfaces (UserBlameInterface and
 * TimestampableInterface) and Structures.
 */
class BlameTimestampSubscriber implements EventSubscriberInterface
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param Factory $factory
     */
    public function __construct(Factory $factory, EntityManager $entityManager)
    {
        $this->factory = $factory;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            SearchEvents::PRE_INDEX => 'handleBlameTimestamp',
            SearchEvents::HIT => 'handleBlameTimestampHitMapping',
        );
    }

    /**
     * Map blame and timestamp information to the search document.
     *
     * @param PreIndexEvent $event
     */
    public function handleBlameTimestamp(PreIndexEvent $event)
    {
        $subject = $event->getSubject();
        $document = $event->getDocument();

        if ($subject instanceof UserBlameInterface) {
            $this->mapCreatorAndChanger($document, $subject->getCreator(), $subject->getChanger());
        }

        if ($subject instanceof TimestampableInterface) {
            $this->mapTimestamp($document, $subject->getCreated(), $subject->getChanged());
        }

        if ($subject instanceof StructureInterface) {
            $this->handleStructure($subject, $document);
        }
    }

    /**
     * Map the changer and created from the field data to
     * the search document (we don't include the field data in the search result API).
     *
     * @param HitEvent $event
     */
    public function handleBlameTimestampHitMapping(HitEvent $event)
    {
        $document = $event->getHit()->getDocument();
        $this->doHandleBlameTimestampHitMapping($document);
    }

    /**
     * @param Document $document
     */
    private function doHandleBlameTimestampHitMapping(Document $document)
    {
        $document->setCreatorName($this->getFieldValue($document, 'creator'));
        $document->setChangerName($this->getFieldValue($document, 'changer'));
        $document->setCreatorId($this->getFieldValue($document, 'creator_id'));
        $document->setChangerId($this->getFieldValue($document, 'changer_id'));
        $document->setCreated($this->getFieldValue($document, 'created'));
        $document->setChanged($this->getFieldValue($document, 'changed'));
    }

    /**
     * Return the named field from the document or return null.
     *
     * @param mixed $document
     * @param mixed $fieldName
     */
    private function getFieldValue($document, $fieldName)
    {
        if (false === $document->hasField($fieldName)) {
            return;
        }

        return $document->getField($fieldName)->getValue();
    }

    /**
     * Handle Sulu Structure objects.
     *
     * @param StructureInterface $structure
     * @param Document $document
     */
    private function handleStructure(StructureInterface $structure, Document $document)
    {
        $creatorId = $structure->getCreator();
        $changerId = $structure->getChanger();
        $creator = null;
        $changer = null;

        // do not try to do this if the Sulu Security component is not registered
        // for some reason.
        //
        // TODO: ->find does not seem to work when using the UserInterface, so we
        //       are now coupled to the Sulu User entity.
        if (class_exists('Sulu\Bundle\SecurityBundle\Entity\User')) {
            if ($creatorId) {
                $creator = $this->entityManager->find(User::class, $creatorId);
            }

            if ($changerId) {
                $changer = $this->entityManager->find(User::class, $changerId);
            }
        }

        $this->mapCreatorAndChanger($document, $creator, $changer);
        $this->mapTimestamp($document, $structure->getCreated(), $structure->getChanged());
    }

    /**
     * Map timestamps to the search document.
     *
     * @param Document $document
     * @param \DateTime $created
     * @param \DateTime $changed
     */
    private function mapTimestamp(Document $document, \DateTime $created = null, \DateTime $changed = null)
    {
        $document->addField(
            $this->factory->createField('created', $created ? $created->format('c') : null, 'string')
        );

        $document->addField(
            $this->factory->createField('changed', $changed ? $changed->format('c') : null, 'string')
        );
    }

    /**
     * Map the creator and changer to the document.
     *
     * @param Document $document
     * @param UserInterface $creator
     * @param UserInterface $changer
     */
    private function mapCreatorAndChanger(Document $document, UserInterface $creator = null, UserInterface $changer = null)
    {
        $document->addField(
            $this->factory->createField('changer', $changer ? $changer->getUsername() : null, 'string')
        );
        $document->addField(
            $this->factory->createField('changer_id', $changer ? $changer->getId() : null, 'string')
        );

        $document->addField(
            $this->factory->createField('creator', $creator ? $creator->getUsername() : null, 'string')
        );
        $document->addField(
            $this->factory->createField('creator_id', $creator ? $creator->getId() : null, 'string')
        );
    }
}
