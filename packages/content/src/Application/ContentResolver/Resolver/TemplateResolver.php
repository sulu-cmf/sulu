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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

class TemplateResolver implements ResolverInterface
{
    public function __construct(
        private MetadataProviderInterface $formMetadataProvider,
        private MetadataResolver $metadataResolver
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent): ContentView
    {
        if (!$dimensionContent instanceof TemplateInterface) {
            throw new \RuntimeException('DimensionContent needs to extend the ' . TemplateInterface::class);
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();
        $templateKey = $dimensionContent->getTemplateKey();
        $templateType = $dimensionContent->getTemplateType();

        /** @var TypedFormMetadata $typedFormMetadata */
        $typedFormMetadata = $this->formMetadataProvider->getMetadata($templateType, $locale, []);
        $formMetadata = $typedFormMetadata->getForms()[$templateKey] ?? null;

        if (!$formMetadata) {
            throw new \RuntimeException(
                'Template with key "' . $templateKey . '" not found. Available keys: ' .
                \implode(', ', \array_keys($typedFormMetadata->getForms()))
            );
        }

        return ContentView::create(
            $this->metadataResolver->resolveItems($formMetadata->getItems(), $dimensionContent->getTemplateData(), $locale),
            []
        );
    }
}
