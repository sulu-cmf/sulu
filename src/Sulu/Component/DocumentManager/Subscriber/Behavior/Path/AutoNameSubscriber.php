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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentHelper;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\NameResolver;
use Sulu\Component\DocumentManager\NodeManager;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Automatically assign a name to the document based on its title.
 *
 * TODO: Refactor MOVE auto-name handling somehow.
 */
class AutoNameSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $scheduledRename = [];

    public function __construct(
        private DocumentRegistry $registry,
        private SlugifierInterface $slugifier,
        private NameResolver $resolver,
        private NodeManager $nodeManager,
        private SessionInterface $session,
        private SessionInterface $liveSession,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CONFIGURE_OPTIONS => 'configureOptions',
            Events::PERSIST => [
                ['handleScheduleRename'],
                ['handlePersist', 480],
            ],
            Events::MOVE => ['handleMove', 480],
            Events::COPY => ['handleCopy', 480],
            Events::FLUSH => ['handleRename', 510],
        ];
    }

    public function configureOptions(ConfigureOptionsEvent $event)
    {
        $options = $event->getOptions();

        $options->setDefaults(
            [
                'auto_name' => true,
                'auto_rename' => true,
                'auto_name_locale' => $this->registry->getDefaultLocale(),
            ]
        );

        $options->setAllowedTypes('auto_name', 'bool');
        $options->setAllowedTypes('auto_rename', 'bool');
        $options->setAllowedTypes('auto_name_locale', ['string', 'null']);
    }

    public function handleMove(MoveEvent $event)
    {
        $this->handleMoveCopy($event);
    }

    public function handleCopy(CopyEvent $event)
    {
        $this->handleMoveCopy($event);
    }

    /**
     * @throws DocumentManagerException
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$event->getOption('auto_name') || !$document instanceof AutoNameBehavior || $event->hasNode()) {
            return;
        }

        $parentNode = $event->getParentNode();
        $name = $this->getName($document, $parentNode, $event->getOption('auto_rename'));
        $node = $parentNode->addNode($name);
        $event->setNode($node);
    }

    /**
     * Renames node if necessary.
     */
    public function handleScheduleRename(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$event->getOption('auto_name')
            || !$document instanceof AutoNameBehavior
            || $event->getOption('auto_name_locale') !== $event->getLocale()
            || !$event->hasNode()
            || $event->getNode()->isNew()
        ) {
            return;
        }

        $node = $event->getNode();
        $name = $this->getName($document, $event->getParentNode(), $event->getOption('auto_rename'), $node);

        if ($name === $node->getName()) {
            return;
        }

        $uuid = $event->getNode()->getIdentifier();
        $this->scheduledRename[] = ['uuid' => $uuid, 'name' => $name, 'locale' => $event->getLocale()];
    }

    public function handleRename()
    {
        foreach ($this->scheduledRename as $item) {
            $defaultNode = $this->session->getNodeByIdentifier($item['uuid']);
            $liveNode = $this->liveSession->getNodeByIdentifier($item['uuid']);
            $this->rename($defaultNode, $item['name']);
            $this->rename($liveNode, $item['name']);

            $document = $this->registry->getDocumentForNode($defaultNode, $item['locale']);

            $accessor = new DocumentAccessor($document);
            if ($document instanceof NodeNameBehavior) {
                $accessor->set('nodeName', $defaultNode->getName());
            }

            if ($document instanceof PathBehavior) {
                $accessor->set('path', $defaultNode->getPath());
            }
        }

        $this->scheduledRename = [];
    }

    /**
     * Returns unique name for given document and nodes.
     *
     * @param bool $autoRename
     *
     * @return string
     *
     * @throws DocumentManagerException
     */
    private function getName(
        AutoNameBehavior $document,
        NodeInterface $parentNode,
        $autoRename = true,
        ?NodeInterface $node = null
    ) {
        $title = $document->getTitle();

        if (!$title) {
            throw new DocumentManagerException(
                \sprintf(
                    'Document has no title (title is required for auto name behavior): %s)',
                    DocumentHelper::getDebugTitle($document)
                )
            );
        }

        $name = $this->slugifier->slugify($title);

        return $this->resolver->resolveName($parentNode, $name, $node, $autoRename);
    }

    /**
     * TODO: This is a workaround for a bug in Jackalope which will be fixed in the next
     *       release 1.2: https://github.com/jackalope/jackalope/pull/262.
     */
    private function rename(NodeInterface $node, $name)
    {
        $names = (array) $node->getParent()->getNodeNames();
        $pos = \array_search($node->getName(), $names);
        $next = isset($names[$pos + 1]) ? $names[$pos + 1] : null;

        $node->rename($name);

        if ($next) {
            $node->getParent()->orderBefore($name, $next);
        }
    }

    /**
     * Resolve the destination name on move and copy events.
     */
    private function handleMoveCopy(MoveEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof AutoNameBehavior) {
            return;
        }

        $destId = $event->getDestId();
        $node = $this->registry->getNodeForDocument($document);
        $destNode = $this->nodeManager->find($destId);
        $nodeName = $this->resolver->resolveName($destNode, $node->getName());

        $event->setDestName($nodeName);
    }
}
