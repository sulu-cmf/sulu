<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Session;

use Iterator;
use PHPCR\CredentialsInterface;
use PHPCR\ItemInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\RepositoryInterface;
use PHPCR\Retention\RetentionManagerInterface;
use PHPCR\Security\AccessControlManagerInterface;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;

/**
 * Used to wrap the PHPCR session and add some Sulu specific logic on top of it.
 */
class Session implements SessionInterface
{
    public function __construct(private SessionInterface $inner)
    {
    }

    public function getRepository(): RepositoryInterface
    {
        return $this->inner->getRepository();
    }

    public function getUserID(): string
    {
        return $this->inner->getUserID();
    }

    public function getAttributeNames(): array
    {
        return $this->inner->getAttributeNames();
    }

    public function getAttribute($name): mixed
    {
        return $this->inner->getAttribute($name);
    }

    public function getWorkspace(): WorkspaceInterface
    {
        return $this->inner->getWorkspace();
    }

    public function getRootNode(): NodeInterface
    {
        return $this->inner->getRootNode();
    }

    public function impersonate(CredentialsInterface $credentials): SessionInterface
    {
        return $this->inner->impersonate($credentials);
    }

    public function getNodeByIdentifier($id): NodeInterface
    {
        return $this->inner->getNodeByIdentifier($id);
    }

    public function getNodesByIdentifier($ids): Iterator
    {
        return $this->inner->getNodesByIdentifier($ids);
    }

    public function getItem($absPath): ItemInterface
    {
        return $this->inner->getItem($absPath);
    }

    public function getNode($absPath, $depthHint = -1): NodeInterface
    {
        return $this->inner->getNode($absPath, $depthHint);
    }

    public function getNodes($absPaths): Iterator
    {
        return $this->inner->getNodes($absPaths);
    }

    public function getProperty($absPath): PropertyInterface
    {
        return $this->inner->getProperty($absPath);
    }

    public function getProperties($absPaths): Iterator
    {
        return $this->inner->getProperties($absPaths);
    }

    public function itemExists($absPath): bool
    {
        return $this->inner->itemExists($absPath);
    }

    public function nodeExists($absPath): bool
    {
        return $this->inner->nodeExists($absPath);
    }

    public function propertyExists($absPath): bool
    {
        return $this->inner->propertyExists($absPath);
    }

    public function move($srcAbsPath, $destAbsPath): void
    {
        $this->inner->move($srcAbsPath, $destAbsPath);
    }

    public function removeItem($absPath): void
    {
        $this->inner->removeItem($absPath);
    }

    public function save(): void
    {
        $this->inner->save();
    }

    public function refresh($keepChanges): void
    {
        $this->inner->refresh($keepChanges);
    }

    public function hasPendingChanges(): bool
    {
        return $this->inner->hasPendingChanges();
    }

    public function hasPermission($absPath, $actions): bool
    {
        return $this->inner->hasPermission($absPath, $actions);
    }

    public function checkPermission($absPath, $actions): void
    {
        $this->inner->checkPermission($absPath, $actions);
    }

    public function hasCapability($methodName, $target, array $arguments): bool
    {
        return $this->inner->hasCapability($methodName, $target, $arguments);
    }

    public function importXML($parentAbsPath, $uri, $uuidBehavior): void
    {
        $this->inner->importXML($parentAbsPath, $uri, $uuidBehavior);
    }

    public function exportSystemView($absPath, $stream, $skipBinary, $noRecurse): void
    {
        $memoryStream = \fopen('php://memory', 'w+');
        $this->inner->exportSystemView($absPath, $memoryStream, $skipBinary, $noRecurse);

        \rewind($memoryStream);
        $content = \stream_get_contents($memoryStream);

        $document = new \DOMDocument();
        $document->loadXML($content);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sv', 'http://www.jcp.org/jcr/sv/1.0');

        foreach ($xpath->query('//sv:property[@sv:name="sulu:versions" or @sv:name="jcr:versionHistory" or @sv:name="jcr:baseVersion" or @sv:name="jcr:predecessors" or @sv:name="jcr:isCheckedOut"]') as $element) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
        }

        \fwrite($stream, $document->saveXML());
    }

    public function exportDocumentView($absPath, $stream, $skipBinary, $noRecurse): void
    {
        $this->inner->exportDocumentView($absPath, $stream, $skipBinary, $noRecurse);
    }

    public function setNamespacePrefix($prefix, $uri): void
    {
        $this->inner->setNamespacePrefix($prefix, $uri);
    }

    public function getNamespacePrefixes(): array
    {
        return $this->inner->getNamespacePrefixes();
    }

    public function getNamespaceURI($prefix): string
    {
        return $this->inner->getNamespaceURI($prefix);
    }

    public function getNamespacePrefix($uri): string
    {
        return $this->inner->getNamespacePrefix($uri);
    }

    public function logout(): void
    {
        $this->inner->logout();
    }

    public function isLive(): bool
    {
        return $this->inner->isLive();
    }

    public function getAccessControlManager(): AccessControlManagerInterface
    {
        return $this->inner->getAccessControlManager();
    }

    public function getRetentionManager(): RetentionManagerInterface
    {
        return $this->inner->getRetentionManager();
    }
}
