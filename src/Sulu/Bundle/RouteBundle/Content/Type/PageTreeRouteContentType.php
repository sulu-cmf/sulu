<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Provides page_tree_route content-type.
 */
class PageTreeRouteContentType extends SimpleContentType
{
    public const NAME = 'page_tree_route';

    public function __construct(
        private DocumentManagerInterface $documentManager,
        private DocumentRegistry $documentRegistry,
        private ChainRouteGeneratorInterface $chainRouteGenerator,
        private ConflictResolverInterface $conflictResolver,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct('PageTreeRoute');
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $propertyName = $property->getName();

        /**
         * If path wouldn't be saved to the property, querying the routes table in getContentData() would be necessary
         * to return the documents url, but for that query the entity class of the current document is needed, which is
         * not available at this point.
         *
         * The disadvantage of saving path to the property is the updating process. If the url of a page changes, each
         * of the child documents of that page have to be changed. Otherwise only the routes would need to be changed.
         * But the website's performance is generally more important than the admin's performance.
         *
         * To change this, there needs to be a way to access the entity class from a property.
         *
         * @see https://github.com/sulu/sulu/issues/5069
         */
        $value = [
            'page' => $this->readPage($propertyName, $node),
            'path' => $node->getPropertyValueWithDefault($propertyName, ''),
            'suffix' => $node->getPropertyValueWithDefault($propertyName . '-suffix', null),
        ];

        $property->setValue($value);

        return $value;
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (!$value) {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);

            $value = [
                'page' => ['uuid' => null, 'path' => '/'],
                'suffix' => null,
            ];
        }

        // Fallback for string value, because the ArticleBundle had a bug where only the route path was saved and not all neccassary values.
        // https://github.com/sulu/SuluArticleBundle/pull/658
        $pageDefault = ['uuid' => null, 'path' => '/'];
        if (\is_array($value)) {
            $page = $this->getAttribute('page', $value, $pageDefault) ?? $pageDefault;
            $suffix = $this->getAttribute('suffix', $value);
        } else {
            $page = $pageDefault;
            $suffix = null;
        }

        if (!$suffix) {
            $suffix = $this->generateSuffix($node, $languageCode);
        } else {
            $suffix = \trim($suffix, '/');
        }

        $path = \rtrim($page['path'], '/') . '/' . $suffix;
        $path = $this->resolveConflicts($path);
        $suffix = '/' . $this->getSuffix($path, $page['path'] ?? '/');

        $propertyName = $property->getName();
        $node->setProperty($propertyName, $path);
        $node->setProperty($propertyName . '-suffix', $suffix);

        $pagePropertyName = $propertyName . '-page';
        $pagePathPropertyName = $pagePropertyName . '-path';
        if (!$page['uuid']) {
            if ($node->hasProperty($pagePropertyName)) {
                $node->getProperty($pagePropertyName)->remove();
            }
            if ($node->hasProperty($pagePathPropertyName)) {
                $node->getProperty($pagePathPropertyName)->remove();
            }

            // no parent-page given

            return;
        }

        $node->setProperty($pagePropertyName, $page['uuid']);
        $node->setProperty($pagePathPropertyName, $page['path']);
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = parent::getContentData($property);

        return $value['path'];
    }

    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * Read page-information from given node.
     *
     * @return mixed[]|null
     */
    private function readPage(string $propertyName, NodeInterface $node): ?array
    {
        $pagePropertyName = $propertyName . '-page';
        if (!$node->hasProperty($pagePropertyName)) {
            return null;
        }

        try {
            $pageUuid = $node->getPropertyValue($pagePropertyName, PropertyType::STRING);
        } catch (ItemNotFoundException $exception) {
            return null;
        }

        return [
            'uuid' => $pageUuid,
            'path' => $node->getPropertyValueWithDefault($pagePropertyName . '-path', ''),
        ];
    }

    /**
     * Get value of array or default.
     *
     * @param mixed[] $value
     */
    private function getAttribute(string $name, array $value, $default = null)
    {
        if (!\array_key_exists($name, $value)) {
            return $default;
        }

        return $value[$name];
    }

    /**
     * Generate a new suffix for document.
     */
    private function generateSuffix(NodeInterface $node, string $locale): string
    {
        $document = $this->documentRegistry->getDocumentForNode($node, $locale);
        $route = $this->chainRouteGenerator->generate($document);

        return \trim($route->getPath(), '/');
    }

    /**
     * Get suffix of given path.
     */
    private function getSuffix(string $path, string $pagePath): string
    {
        return \trim(\substr($path, \strlen(\rtrim($pagePath, '/')) + 1), '/');
    }

    /**
     * Resolve conflicts of given path.
     */
    private function resolveConflicts(string $path): string
    {
        $route = $this->getRouteRepository()->createNew();
        $route->setPath($path);
        $route = $this->conflictResolver->resolve($route);

        return $route->getPath();
    }

    /**
     * Get RouteRepository.
     */
    private function getRouteRepository(): RouteRepositoryInterface
    {
        return $this->entityManager->getRepository(Route::class);
    }
}
