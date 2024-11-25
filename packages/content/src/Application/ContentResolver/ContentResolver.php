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

namespace Sulu\Content\Application\ContentResolver;

use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Resolver\TemplateResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ContentResolver implements ContentResolverInterface
{
    /**
     * @param iterable<ResolverInterface> $contentResolvers
     */
    public function __construct(
        private iterable $contentResolvers,
        private ResourceLoaderProvider $resourceLoaderProvider
    ) {
    }

    /**
     * @return array{
     *      resource: object,
     *      content: mixed,
     *      view: mixed[]
     *  }
     */
    public function resolve(DimensionContentInterface $dimensionContent): array
    {
        $contentViews = [];
        foreach ($this->contentResolvers as $resolverKey => $contentResolver) {
            $contentView = $contentResolver->resolve($dimensionContent);

            if ($contentResolver instanceof TemplateResolver) {
                /** @var mixed[] $content */
                $content = $contentView->getContent();
                $contentViews = \array_merge($contentViews, $content);
                continue;
            }

            $contentViews[$resolverKey] = $contentView;
        }
        $resolvedContent = $this->resolveContentViews($contentViews);
        $resolvedResources = $this->loadAndResolveResources($resolvedContent['resolvableResources'], $dimensionContent->getLocale());
        $content = $this->replaceResolvableResourcesWithResolvedValues($resolvedContent['content'], $resolvedResources);

        return [
            'resource' => $dimensionContent->getResource(),
            'content' => $content,
            'view' => $resolvedContent['view'],
        ];
    }

    /**
     * @param ContentView[] $contentViews
     *
     * @return array{
     *     content: mixed[],
     *     view: mixed[],
     *     resolvableResources: array<string, array<ResolvableResource>>
     *     }
     */
    private function resolveContentViews(array $contentViews): array
    {
        $content = [];
        $view = [];
        $resolvableResources = [];

        foreach ($contentViews as $name => $contentView) {
            $result = $this->resolveContentView($contentView, (string) $name);
            $content = \array_merge($content, $result['content']);
            $view = \array_merge($view, $result['view']);
            $resolvableResources = \array_merge_recursive($resolvableResources, $result['resolvableResources']);
        }

        return [
            'content' => $content,
            'view' => $view,
            'resolvableResources' => $resolvableResources,
        ];
    }

    /**
     * @return array{
     *     content: mixed[],
     *     view: mixed[],
     *     resolvableResources: array<string, array<ResolvableResource>>
     *     }
     */
    private function resolveContentView(ContentView $contentView, string $name): array
    {
        $content = $contentView->getContent();
        $view = $contentView->getView();

        $result = [
            'content' => [],
            'view' => [],
            'resolvableResources' => [],
        ];
        if (\is_array($content)) {
            if (\count(\array_filter($content, fn ($entry) => $entry instanceof ContentView)) === \count($content)) {
                // resolve array of content views
                $resolvedContentViews = $this->resolveContentViews($content);
                $result['content'][$name] = $resolvedContentViews['content'];
                $result['view'][$name] = $resolvedContentViews['view'];
                $result['resolvableResources'] = \array_merge_recursive($result['resolvableResources'], $resolvedContentViews['resolvableResources']);

                return $result;
            }

            $resolvableResources = [];
            foreach ($content as $key => $entry) {
                // resolve array of mixed content
                if ($entry instanceof ContentView) {
                    $resolvedContentView = $this->resolveContentView($entry, $key);
                    $result['content'][$name] = \array_merge($result['content'][$name] ?? [], $resolvedContentView['content']);
                    $result['view'][$name] = \array_merge($result['view'][$name] ?? [], $resolvedContentView['view']);
                    $resolvableResources = \array_merge_recursive($resolvableResources, $resolvedContentView['resolvableResources']);

                    continue;
                }

                if ($entry instanceof ResolvableResource) {
                    $resolvableResources[$entry->getResourceLoaderKey()][] = $entry;
                }

                $result['content'][$name][$key] = $entry;
                $result['view'][$name][$key] = [];
            }

            $result['resolvableResources'] = $resolvableResources;

            return $result;
        }

        if ($content instanceof ResolvableResource) {
            $result['resolvableResources'][$content->getResourceLoaderKey()][] = $content;
        }

        $result['content'][$name] = $content;
        $result['view'][$name] = $view;

        return $result;
    }

    /**
     * Loads and resolves resources from various resource loaders.
     *
     * @param array<string, array<ResolvableResource>> $resourcesPerLoader Resource loaders and their associated resources to load
     *
     * @return array<string, mixed[]> Resolved resources organized by resource loader key
     */
    private function loadAndResolveResources(array $resourcesPerLoader, ?string $locale): array
    {
        $resolvedResources = [];

        foreach ($resourcesPerLoader as $loaderKey => $resourcesToLoad) {
            if (!$loaderKey) {
                throw new \RuntimeException(\sprintf('ResourceLoader key "%s" is invalid', $loaderKey));
            }

            $resourceLoader = $this->resourceLoaderProvider->getResourceLoader($loaderKey);
            if (!$resourceLoader) {
                throw new \RuntimeException(\sprintf('ResourceLoader with key "%s" not found', $loaderKey));
            }

            $resourceIds = \array_map(fn (ResolvableResource $resource) => $resource->getId(), $resourcesToLoad);
            $resolvedResources[$loaderKey] = $resourceLoader->load(
                $resourceIds,
                $locale
            );
        }

        return $resolvedResources;
    }

    /**
     * Replaces all instances of ResolvableResource in the given content with their resolved values.
     *
     * @param mixed[] $content The content to replace ResolvableResource instances
     * @param array<string, mixed[]> $resolvedResources The resolved resources, indexed by resource loader key and objectHash
     *
     * @return mixed[] The content with all ResolvableResource instances replaced with their resolved values
     */
    private function replaceResolvableResourcesWithResolvedValues(array $content, array $resolvedResources): array
    {
        \array_walk_recursive($content, function(&$value) use ($resolvedResources) {
            if ($value instanceof ResolvableResource) {
                $value = $value->executeResourceCallback(
                    $resolvedResources[$value->getResourceLoaderKey()][$value->getId()]
                );
            }
        });

        return $content;
    }
}
