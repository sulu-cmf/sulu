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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver;

use Psr\Log\LoggerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Bundle\ContentBundle\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Bundle\ContentBundle\Content\Application\PropertyResolver\PropertyResolverInterface;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;

/**
 * @internal if you need to override this service, create a new service with based on ResourceLoaderInterface instead of extending this class
 *
 * @final
 */
class ImageMapPropertyResolver implements PropertyResolverInterface // TODO we may should implement a PropertyResolverAwareMetadataInterface
{
    private MetadataResolver $metadataResolver;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    /**
     * @internal
     *
     * Prevent circular dependency by injecting the MetadataResolver after instantiation
     */
    public function setMetadataResolver(MetadataResolver $metadataResolver): void
    {
        $this->metadataResolver = $metadataResolver;
    }

    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $hotspots = (\is_array($data) && isset($data['hotspots']) && \is_array($data['hotspots'])) && \array_is_list($data['hotspots'])
            ? $data['hotspots']
            : [];

        $hotspots = [] !== $hotspots ? $this->resolveHotspots($hotspots, $locale, $params) : ContentView::create([], []);

        $returnedParams = $params;
        unset($returnedParams['metadata']); // TODO we may should implement a PropertyResolverAwareMetadataInterface

        if (!\is_array($data)
            || !isset($data['imageId'])
            || !\is_numeric($data['imageId'])
        ) {
            return ContentView::create([
                'image' => null,
                'hotspots' => $hotspots->getContent(),
            ], [
                'imageId' => null,
                'hotspots' => $hotspots->getView(),
                ...$returnedParams,
            ]);
        }

        /** @var string $resourceLoaderKey */
        $resourceLoaderKey = $params['resourceLoader'] ?? MediaResourceLoader::getKey();
        $imageId = (int) $data['imageId'];

        return ContentView::create(
            [
                'image' => new ResolvableResource($imageId, $resourceLoaderKey),
                'hotspots' => $hotspots->getContent(),
            ],
            [
                'imageId' => $imageId,
                'hotspots' => $hotspots->getView(),
                ...$returnedParams,
            ],
        );
    }

    /**
     * @param non-empty-array<array<mixed>> $hotspots
     * @param array<string, mixed> $params
     */
    private function resolveHotspots(array $hotspots, string $locale, array $params): ContentView
    {
        $metadata = $params['metadata'] ?? null;
        \assert($metadata instanceof FieldMetadata, 'Metadata must be set to resolve hotspots.');
        $metadataTypes = $metadata->getTypes();
        $content = [];
        $view = [];
        foreach ($hotspots as $key => $block) {
            if (!\is_array($block) || !isset($block['type']) || !\is_string($block['type'])) {
                continue;
            }
            if (!isset($block['hotspot']) || !\is_array($block['hotspot'])) {
                continue;
            }

            $type = $block['type'];
            $formMetadata = $metadataTypes[$type] ?? null;

            if (!$formMetadata instanceof FormMetadata) {
                $errorMessage = \sprintf(
                    'Metadata type "%s" in "%s" not found, founded types are: "%s"',
                    $type,
                    $metadata->getName(),
                    \implode('", "', \array_keys($metadataTypes)),
                );

                $this->logger->error($errorMessage);

                if ($this->debug) {
                    throw new \UnexpectedValueException($errorMessage);
                }

                $type = $metadata->getDefaultType();
                $formMetadata = $metadataTypes[$type] ?? null;
                if (!$formMetadata instanceof FormMetadata) {
                    continue;
                }
            }

            $content[$key] = [
                'type' => $type,
                'hotspot' => $block['hotspot'],
            ];

            $view[$key] = [];

            foreach ($this->metadataResolver->resolveItems($formMetadata->getItems(), $block, $locale) as $field => $resolvedItem) {
                $content[$key][$field] = $resolvedItem->getContent();
                $view[$key][$field] = $resolvedItem->getView();
            }
        }

        return ContentView::create(\array_values($content), \array_values($view));
    }

    public static function getType(): string
    {
        return 'image_map';
    }
}
