<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior;

use Jackalope\Version\VersionManager;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use PHPCR\Version\VersionException;
use Sulu\Component\DocumentManager\Behavior\VersionBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\VersionNotFoundException;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Version;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber is responsible for creating versions of a document.
 */
class VersionSubscriber implements EventSubscriberInterface
{
    public const VERSION_PROPERTY = 'sulu:versions';

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var string[]
     */
    private $checkoutUuids = [];

    /**
     * @var string[]
     */
    private $checkpointUuids = [];

    public function __construct(
        private SessionInterface $defaultSession,
        private PropertyEncoder $propertyEncoder,
    ) {
        $this->versionManager = $this->defaultSession->getWorkspace()->getVersionManager();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PERSIST => [
                ['setVersionMixin', 468],
                ['rememberCheckoutUuids', -512],
            ],
            Events::PUBLISH => [
                ['setVersionMixin', 468],
                ['rememberCreateVersion'],
            ],
            Events::HYDRATE => 'setVersionsOnDocument',
            Events::FLUSH => 'applyVersionOperations',
            Events::RESTORE => 'restoreProperties',
        ];
    }

    /**
     * Sets the versionable mixin on the node if it is a versionable document.
     */
    public function setVersionMixin(AbstractMappingEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            return;
        }

        $event->getNode()->addMixin('mix:versionable');
    }

    /**
     * Sets the version information set on the node to the document.
     */
    public function setVersionsOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->support($document)) {
            return;
        }

        $node = $event->getNode();

        $versions = [];
        $versionProperty = $node->getPropertyValueWithDefault(static::VERSION_PROPERTY, []);
        foreach ($versionProperty as $version) {
            $versionInformation = \json_decode($version);
            $versions[] = new Version(
                $versionInformation->version,
                $versionInformation->locale,
                $versionInformation->author,
                new \DateTime($versionInformation->authored)
            );
        }

        $document->setVersions($versions);
    }

    /**
     * Remember which uuids need to be checked out after everything has been saved.
     */
    public function rememberCheckoutUuids(PersistEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            return;
        }

        $this->checkoutUuids[] = $event->getNode()->getIdentifier();
    }

    /**
     * Remember for which uuids a new version has to be created.
     */
    public function rememberCreateVersion(PublishEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->support($document)) {
            return;
        }

        $this->checkpointUuids[] = [
            'uuid' => $event->getNode()->getIdentifier(),
            'locale' => $document->getLocale(),
            'author' => $event->getOption('user'),
        ];
    }

    /**
     * Apply all the operations which have been remembered after the flush.
     */
    public function applyVersionOperations()
    {
        foreach ($this->checkoutUuids as $uuid) {
            $node = $this->defaultSession->getNodeByIdentifier($uuid);
            $path = $node->getPath();

            if (!$this->versionManager->isCheckedOut($path)) {
                $this->versionManager->checkout($path);
            }
        }

        $this->checkoutUuids = [];

        /** @var NodeInterface[] $nodes */
        $nodes = [];
        $nodeVersions = [];
        foreach ($this->checkpointUuids as $versionInformation) {
            $node = $this->defaultSession->getNodeByIdentifier($versionInformation['uuid']);
            $path = $node->getPath();

            $version = $this->versionManager->checkpoint($path);

            if (!\array_key_exists($path, $nodes)) {
                $nodes[$path] = $this->defaultSession->getNode($path);
            }
            $versions = $nodes[$path]->getPropertyValueWithDefault(static::VERSION_PROPERTY, []);

            if (!\array_key_exists($path, $nodeVersions)) {
                $nodeVersions[$path] = $versions;
            }
            $nodeVersions[$path][] = \json_encode(
                [
                    'locale' => $versionInformation['locale'],
                    'version' => $version->getName(),
                    'author' => $versionInformation['author'],
                    'authored' => \date('c'),
                ]
            );
        }

        foreach ($nodes as $path => $node) {
            $node->setProperty(static::VERSION_PROPERTY, $nodeVersions[$path]);
        }

        $this->defaultSession->save();
        $this->checkpointUuids = [];
    }

    /**
     * Restore the properties of the old version.
     *
     * @throws VersionNotFoundException
     */
    public function restoreProperties(RestoreEvent $event)
    {
        if (!$this->support($event->getDocument())) {
            $event->stopPropagation();

            return;
        }

        $contentPropertyPrefix = $this->propertyEncoder->localizedContentName('', $event->getLocale());
        $systemPropertyPrefix = $this->propertyEncoder->localizedSystemName('', $event->getLocale());

        $node = $event->getNode();

        try {
            $version = $this->versionManager->getVersionHistory($node->getPath())->getVersion($event->getVersion());

            $frozenNode = $version->getFrozenNode();

            $this->restoreNodeProperties($node, $frozenNode, $contentPropertyPrefix, $systemPropertyPrefix);
        } catch (VersionException $exception) {
            throw new VersionNotFoundException($event->getDocument(), $event->getVersion());
        }
    }

    /**
     * Restore given node with properties given from frozen-node.
     * Will be called recursive.
     *
     * @param string $contentPropertyPrefix
     * @param string $systemPropertyPrefix
     */
    private function restoreNodeProperties(
        NodeInterface $node,
        NodeInterface $frozenNode,
        $contentPropertyPrefix,
        $systemPropertyPrefix
    ) {
        // remove the properties for the given language, so that values being added since the last version are removed
        foreach ($node->getProperties() as $property) {
            if ($this->isRestoreProperty($property->getName(), $contentPropertyPrefix, $systemPropertyPrefix)) {
                $property->remove();
            }
        }

        // set all the properties from the saved version to the node
        foreach ($frozenNode->getPropertiesValues() as $name => $value) {
            if ($this->isRestoreProperty($name, $contentPropertyPrefix, $systemPropertyPrefix)) {
                $node->setProperty($name, $value);
            }
        }
    }

    /**
     * @param string $propertyName
     * @param string $contentPrefix
     * @param string $systemPrefix
     *
     * @return bool
     */
    private function isRestoreProperty($propertyName, $contentPrefix, $systemPrefix)
    {
        // return all localized and non-translatable properties
        // non-translatable properties can be recognized by their missing namespace, therfore the check for the colon
        if (0 === \strpos($propertyName, $contentPrefix)
            || 0 === \strpos($propertyName, $systemPrefix)
            || false === \strpos($propertyName, ':')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the given document supports versioning.
     *
     * @param object $document
     *
     * @return bool
     */
    private function support($document)
    {
        return $document instanceof VersionBehavior;
    }
}
