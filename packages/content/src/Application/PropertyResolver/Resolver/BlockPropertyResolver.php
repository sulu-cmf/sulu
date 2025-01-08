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

namespace Sulu\Content\Application\PropertyResolver\Resolver;

use Psr\Log\LoggerInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;

class BlockPropertyResolver implements PropertyResolverInterface
{
    private MetadataResolver $metadataResolver;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    /**
     * Prevent circular dependency by injecting the MetadataResolver after instantiation.
     */
    public function setMetadataResolver(MetadataResolver $metadataResolver): void
    {
        $this->metadataResolver = $metadataResolver;
    }

    /**
     * @param array<array<mixed>>|mixed $data
     */
    public function resolve(mixed $data, string $locale, array $params = []): ContentView
    {
        $metadata = $params['metadata'] ?? null;
        $returnedParams = $params;
        unset($returnedParams['metadata']); // TODO we may should implement a PropertyResolverAwareMetadataInterface

        if (!\is_array($data) || !\array_is_list($data)) {
            return ContentView::create([], [...$returnedParams]);
        }

        \assert($metadata instanceof FieldMetadata, 'Metadata must be set to resolve blocks.');
        $metadataTypes = $metadata->getTypes();

        $contentViews = [];
        foreach ($data as $key => $block) {
            if (!\is_array($block) || !isset($block['type']) || !\is_string($block['type'])) {
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

            $contentViews[$key] = ContentView::create(
                \array_merge(
                    ['type' => $type],
                    $this->metadataResolver->resolveItems($formMetadata->getItems(), $block, $locale)
                ),
                [
                    ...$returnedParams,
                ]
            );
        }

        return ContentView::create($contentViews, [...$returnedParams]);
    }

    public static function getType(): string
    {
        return 'block';
    }
}
