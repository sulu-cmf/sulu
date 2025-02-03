<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Search\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Massive\Bundle\SearchBundle\Search\Event\HitEvent;
use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\SearchBundle\Search\Document;
use Sulu\Component\Persistence\Model\TimestampableInterface;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add blame (creator, changor) and timestamp (created, changed) to
 * the document before it is indexed.
 *
 * Works for objects implementing interfaces (UserBlameInterface and
 * TimestampableInterface).
 */
class BlameTimestampSubscriber implements EventSubscriberInterface
{
    public function __construct(private Factory $factory, private EntityManager $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchEvents::PRE_INDEX => 'handleBlameTimestamp',
            SearchEvents::HIT => 'handleBlameTimestampHitMapping',
        ];
    }

    /**
     * Map blame and timestamp information to the search document.
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
    }

    /**
     * Map the changer and created from the field data to
     * the search document (we don't include the field data in the search result API).
     */
    public function handleBlameTimestampHitMapping(HitEvent $event)
    {
        $document = $event->getHit()->getDocument();
        $this->doHandleBlameTimestampHitMapping($document);
    }

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
     */
    private function getFieldValue($document, $fieldName)
    {
        if (false === $document->hasField($fieldName)) {
            return;
        }

        return $document->getField($fieldName)->getValue();
    }

    /**
     * Map timestamps to the search document.
     */
    private function mapTimestamp(Document $document, ?\DateTime $created = null, ?\DateTime $changed = null)
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
     */
    private function mapCreatorAndChanger(Document $document, ?UserInterface $creator = null, ?UserInterface $changer = null)
    {
        $document->addField(
            $this->factory->createField('changer', $changer ? $changer->getUserIdentifier() : null, 'string')
        );
        $document->addField(
            $this->factory->createField('changer_id', $changer ? $changer->getId() : null, 'string')
        );

        $document->addField(
            $this->factory->createField('creator', $creator ? $creator->getUserIdentifier() : null, 'string')
        );
        $document->addField(
            $this->factory->createField('creator_id', $creator ? $creator->getId() : null, 'string')
        );
    }
}
