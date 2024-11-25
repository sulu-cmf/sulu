<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class RoutableDataMapper implements DataMapperInterface
{
    /**
     * @var StructureMetadataFactoryInterface
     */
    private $factory;

    /**
     * @var RouteGeneratorInterface
     */
    private $routeGenerator;

    /**
     * @var RouteManagerInterface
     */
    private $routeManager;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @var array<string, array<mixed>>
     */
    private $routeMappings;

    /**
     * @param array<string, array<mixed>> $routeMappings
     */
    public function __construct(
        StructureMetadataFactoryInterface $factory,
        RouteGeneratorInterface $routeGenerator,
        RouteManagerInterface $routeManager,
        ConflictResolverInterface $conflictResolver,
        array $routeMappings
    ) {
        $this->factory = $factory;
        $this->routeGenerator = $routeGenerator;
        $this->routeManager = $routeManager;
        $this->conflictResolver = $conflictResolver;
        $this->routeMappings = $routeMappings;
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof RoutableInterface) {
            return;
        }

        if (!$localizedDimensionContent instanceof TemplateInterface) {
            throw new \RuntimeException('LocalizedDimensionContent needs to extend the TemplateInterface.');
        }

        $type = $localizedDimensionContent::getTemplateType();

        /** @var string|null $template */
        $template = $localizedDimensionContent->getTemplateKey() ?? null;

        if (null === $template) {
            throw new \RuntimeException('LocalizedDimensionContent should return the a template.');
        }

        $metadata = $this->factory->getStructureMetadata($type, $template);
        if (!$metadata) {
            return;
        }

        $property = $this->getRouteProperty($metadata);

        if (!$property) {
            return;
        }

        $locale = $localizedDimensionContent->getLocale();
        if (!$locale) {
            throw new \RuntimeException('Expected a LocalizedDimensionContent with a locale.');
        }

        /** @var string $name */
        $name = $property->getName();
        if ('url' !== $name) {
            throw new \RuntimeException(\sprintf(
                'Expected a property with the name "url" but "%s" given.',
                $name
            )); // TODO move this validation to a compiler pass see also direct access of 'url'  in PublishTransitionSubscriber class.
        }

        $currentRoutePath = $localizedDimensionContent->getTemplateData()[$name] ?? null;
        if (!\array_key_exists($name, $data) && null !== $currentRoutePath) {
            return;
        }

        $entityClass = null;
        $routeSchema = null;
        $resourceKey = $localizedDimensionContent::getResourceKey();

        foreach ($this->routeMappings as $key => $mapping) {
            if ($resourceKey === $mapping['resource_key']) {
                $entityClass = $mapping['entityClass'] ?? $key;
                $routeSchema = $mapping['options'];
                break;
            }
        }

        if (null === $entityClass || null === $routeSchema) {
            throw new \RuntimeException(\sprintf('No route mapping found for "%s".', $resourceKey));
        }

        /** @var string $routePath */
        $routePath = $data[$name] ?? '';

        if (!$routePath) {
            /** @var mixed[] $routeGenerationData */
            $routeGenerationData = \array_merge(
                $data,
                [
                    '_unlocalizedObject' => $unlocalizedDimensionContent,
                    '_localizedObject' => $localizedDimensionContent,
                ]
            );

            $routePath = $this->routeGenerator->generate(
                $routeGenerationData,
                $routeSchema
            );
        }

        if ('/' === $routePath) {
            throw new \RuntimeException('Not allowed url "/" given or generated.');
        }

        if (DimensionContentInterface::STAGE_LIVE === $localizedDimensionContent->getStage()) {
            if (!$localizedDimensionContent->getResourceId()) {
                // TODO route bundle should work to update the entity later with a resourceId over UPDATE SQL statement
                throw new \RuntimeException('Expected a LocalizedDimensionContent with a resourceId.');
            }

            // route should only be updated in live dimension
            $route = $this->routeManager->createOrUpdateByAttributes(
                $entityClass,
                (string) $localizedDimensionContent->getResourceId(),
                $locale,
                $routePath,
                false
            );

            $routePath = $route->getPath();
        } else {
            $route = new Route();
            $route->setPath($routePath);
            $route->setLocale($locale);
            $route->setEntityClass($entityClass);
            $route->setEntityId((string) $localizedDimensionContent->getResourceId());

            $routePath = $this->conflictResolver->resolve($route)
                ->getPath();
        }

        $oldData = $localizedDimensionContent->getTemplateData();
        if (($oldData[$name] ?? null) !== $routePath) {
            $localizedDimensionContent->setTemplateData(
                \array_merge(
                    $oldData,
                    [$name => $routePath]
                )
            );
        }
    }

    private function getRouteProperty(StructureMetadata $metadata): ?PropertyMetadata
    {
        foreach ($metadata->getProperties() as $property) {
            // TODO add support for page_tree_route field type: https://github.com/sulu/SuluContentBundle/issues/242
            if ('route' === $property->getType()) {
                return $property;
            }
        }

        return null;
    }
}
