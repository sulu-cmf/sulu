<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Path;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Behavior\Path\AliasFilingBehavior;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;

/**
 * Automatically set the parent at a pre-determined location.
 */
class AliasFilingSubscriber extends AbstractFilingSubscriber
{
    /**
     * @var Inflector
     */
    private $inflector;

    public function __construct(
        SessionInterface $defaultSession,
        SessionInterface $liveSession,
        private MetadataFactoryInterface $metadataFactory
    ) {
        parent::__construct($defaultSession, $liveSession);
        $this->inflector = InflectorFactory::create()->build();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => ['handlePersist', 490],
        ];
    }

    protected function generatePath(PersistEvent $event)
    {
        $document = $event->getDocument();

        $currentPath = '';
        if ($event->hasParentNode()) {
            $currentPath = $event->getParentNode()->getPath();
        }
        $parentName = $this->getParentName($document);

        return \sprintf('%s/%s', $currentPath, $this->inflector->pluralize($parentName));
    }

    /**
     * @param object $document
     *
     * @return bool
     */
    protected function supports($document)
    {
        return $document instanceof AliasFilingBehavior;
    }

    /**
     * @param object $document
     *
     * @return string
     */
    protected function getParentName($document)
    {
        return $this->metadataFactory->getMetadataForClass(\get_class($document))->getAlias();
    }
}
