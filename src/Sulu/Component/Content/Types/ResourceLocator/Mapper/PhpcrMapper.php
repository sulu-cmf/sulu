<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Mapper;

use PHPCR\ItemExistsException;
use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyInterface;
use PHPCR\Util\PathHelper;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Manages resource-locators in phpcr.
 */
class PhpcrMapper implements ResourceLocatorMapperInterface
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $documentInspector,
    ) {
    }

    public function save(ResourceSegmentBehavior $document)
    {
        $path = $document->getResourceSegment();

        $webspaceKey = $this->documentInspector->getWebspace($document);
        $locale = $this->documentInspector->getOriginalLocale($document);
        $segmentKey = null;
        $webspaceRouteRootPath = $this->getWebspaceRouteNodeBasePath($webspaceKey, $locale, $segmentKey);

        try {
            $routeNodePath = $this->loadByContent(
                $this->documentInspector->getNode($document),
                $webspaceKey,
                $locale,
                $segmentKey
            );

            $routeDocument = $this->documentManager->find(
                $webspaceRouteRootPath . $routeNodePath,
                $locale,
                ['rehydrate' => false]
            );
            $routeDocumentPath = $webspaceRouteRootPath . $routeNodePath;
        } catch (ResourceLocatorNotFoundException $e) {
            $routeDocument = $this->documentManager->create('route');
            $routeDocumentPath = $webspaceRouteRootPath . $path;
        }

        $routeDocument->setTargetDocument($document);

        try {
            $this->documentManager->persist(
                $routeDocument,
                $locale,
                [
                    'path' => $routeDocumentPath,
                    'auto_create' => true,
                    'override' => true,
                ]
            );
            $this->documentManager->publish($routeDocument, $locale);
        } catch (ItemExistsException $e) {
            throw new ResourceLocatorAlreadyExistsException($document->getResourceSegment(), $routeDocumentPath, $e);
        }
    }

    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $result = $this->iterateRouteNodes(
            $contentNode,
            function($resourceLocator, NodeInterface $node) {
                if (false === $node->getPropertyValue('sulu:history') && false !== $resourceLocator) {
                    return $resourceLocator;
                }

                return false;
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        if (null !== $result) {
            return $result;
        }

        throw new ResourceLocatorNotFoundException();
    }

    /**
     * Iterates over all route nodes assigned by the given node, and executes the callback on it.
     *
     * @param callable $callback will be called foreach route node (stops and return value if not false)
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return NodeInterface|null
     */
    private function iterateRouteNodes(
        NodeInterface $node,
        $callback,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        if ($node->isNew()) {
            return null;
        }

        $routePath = $this->sessionManager->getRoutePath($webspaceKey, $languageCode);

        // search for references with name 'content'
        foreach ($node->getReferences('sulu:content') as $ref) {
            if ($ref instanceof PropertyInterface) {
                $routeNode = $ref->getParent();
                if (0 !== \strpos($routeNode->getPath(), $routePath)) {
                    continue;
                }

                $resourceLocator = $this->getResourceLocator(
                    $ref->getParent()->getPath(),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                $result = $callback($resourceLocator, $routeNode);
                if (false !== $result) {
                    return $result;
                }
            }
        }

        return null;
    }

    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByContent($contentNode, $webspaceKey, $languageCode, $segmentKey);
    }

    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // get content node
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        // get current path node
        $pathNode = $this->iterateRouteNodes(
            $contentNode,
            function($resourceLocator, NodeInterface $node) {
                if (false === $node->getPropertyValue('sulu:history') && false !== $resourceLocator) {
                    return $node;
                } else {
                    return false;
                }
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        // iterate over history of path node
        $result = [];

        if (!$pathNode) {
            return $result;
        }

        $this->iterateRouteNodes(
            $pathNode,
            function($resourceLocator, NodeInterface $node) use (&$result) {
                if (false !== $resourceLocator) {
                    // add resourceLocator
                    $result[] = new ResourceLocatorInformation(
                        //backward compability
                        $resourceLocator,
                        $node->getPropertyValueWithDefault('sulu:created', new \DateTime()),
                        $node->getIdentifier()
                    );
                }

                return false;
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        // sort history descending
        \usort(
            $result,
            function(ResourceLocatorInformation $item1, ResourceLocatorInformation $item2) {
                return $item2->getCreated() <=> $item1->getCreated();
            }
        );

        return $result;
    }

    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $resourceLocator = \ltrim($resourceLocator, '/');

        $path = \sprintf(
            '%s/%s',
            $this->getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey),
            $resourceLocator
        );

        try {
            if ('' !== $resourceLocator) {
                if (!PathHelper::assertValidAbsolutePath($path, false, false)) {
                    throw new ResourceLocatorNotFoundException(\sprintf('Path "%s" not found', $path));
                }

                // get requested resource locator route node
                $route = $this->sessionManager->getSession()->getNode($path);
            } else {
                // get home page route node
                $route = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);
            }
        } catch (PathNotFoundException $e) {
            throw new ResourceLocatorNotFoundException(\sprintf('Path "%s" not found', $path), 0, $e);
        }

        if ($route->hasProperty('sulu:content') && $route->hasProperty('sulu:history')) {
            if (!$route->getPropertyValue('sulu:history')) {
                /** @var NodeInterface $content */
                $content = $route->getPropertyValue('sulu:content');

                return $content->getIdentifier();
            } else {
                // get path from history node
                /** @var NodeInterface $realPath */
                $realPath = $route->getPropertyValue('sulu:content');

                $locator = $this->getResourceLocator($realPath->getPath(), $webspaceKey, $languageCode, $segmentKey);
                if (false === $locator) {
                    throw new ResourceLocatorNotFoundException(\sprintf(
                        'Couldn\'t generate resource locator for path: %s',
                        $realPath->getPath()
                    ));
                }

                throw new ResourceLocatorMovedException($locator, $realPath->getIdentifier());
            }
        } else {
            throw new ResourceLocatorNotFoundException(\sprintf(
                'Route has "%s" does not have either the "sulu:content" or "sulu:history" properties',
                $route->getPath()
            ));
        }
    }

    public function unique($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        return $this->isUnique($routes, $path);
    }

    public function getUniquePath($path, $webspaceKey, $languageCode, $segmentKey = null/*, $uuid = null*/)
    {
        $uuid = null;

        if (\func_num_args() >= 5) {
            $uuid = \func_get_arg(4);
        }

        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        if ($this->isUnique($routes, $path, $uuid)) {
            // path is already unique
            return $path;
        } else {
            // append -
            $path .= '-';
            // init counter
            $i = 1;
            // while $path-$i is not unique raise counter
            while (!$this->isUnique($routes, $path . $i, $uuid)) {
                ++$i;
            }

            // result is unique
            return $path . $i;
        }
    }

    public function deleteById($id, $languageCode, $segmentKey = null)
    {
        $routeDocument = $this->documentManager->find($id, $languageCode);
        $this->documentManager->remove($routeDocument);
    }

    public function getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);
        $parentNode = $contentNode->getParent();

        try {
            return $this->loadByContent($parentNode, $webspaceKey, $languageCode, $segmentKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            // parent node don´t have a resource locator
            return;
        }
    }

    /**
     * Check if path is unique from given $root node.
     *
     * @param NodeInterface $root route node
     * @param string $path requested path
     *
     * @return bool path is unique
     */
    private function isUnique(NodeInterface $root, $path, $uuid = null)
    {
        $path = \ltrim($path, '/');

        if (!$root->hasNode($path)) {
            return true;
        }

        if (!$uuid) {
            return false;
        }

        $route = $root->getNode($path);

        return $route->hasProperty('sulu:content')
            && $route->getPropertyValue('sulu:content')->getIdentifier() === $uuid;
    }

    /**
     * Returns base node of routes from phpcr.
     *
     * @param string $webspaceKey current session
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return NodeInterface base node of routes
     */
    private function getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey)
    {
        return $this->sessionManager->getRouteNode($webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * Returns base path of routes from phpcr.
     *
     * @param string $webspaceKey current session
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    private function getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey)
    {
        return $this->sessionManager->getRoutePath($webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * Returns resource-locator.
     *
     * @param string $path
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string|false
     */
    private function getResourceLocator($path, $webspaceKey, $languageCode, $segmentKey)
    {
        $basePath = $this->getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey);
        if ($path === $basePath) {
            return '/';
        }

        if (false !== \strpos($path, $basePath . '/')) {
            $result = \str_replace($basePath . '/', '/', $path);
            if (0 === \strpos($result, '/')) {
                return $result;
            }
        }

        return false;
    }
}
