<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Reference\Provider;

use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Extension\ReferenceExtensionInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This class is also extended by the PageBundle.
 *
 * @see \Sulu\Bundle\PageBundle\Reference\Provider\PageReferenceProvider
 * @see \Sulu\Bundle\SnippetBundle\Reference\Provider\SnippetReferenceProvider
 *
 * @internal
 */
abstract class AbstractDocumentReferenceProvider implements DocumentReferenceProviderInterface
{
    private ContentTypeManagerInterface $contentTypeManager;

    private StructureManagerInterface $structureManager;

    private ExtensionManagerInterface $extensionManager;

    private ReferenceRepositoryInterface $referenceRepository;

    private string $structureType;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ReferenceRepositoryInterface $referenceRepository,
        string $structureType,
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->referenceRepository = $referenceRepository;
        $this->structureType = $structureType;
    }

    abstract public static function getResourceKey(): string;

    public function updateReferences($document, string $locale, string $context): void
    {
        $referenceResourceKey = $this->getReferenceResourceKey($document);

        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            $referenceResourceKey,
            $document->getUuid(),
            $locale,
            $document->getTitle(),
            $context,
            $this->getReferenceRouterAttributes($document, $locale),
        );

        $structure = $document->getStructure();

        if (null === $document->getStructureType()) {
            // if there is no structureType we cannot update the references
            // this happens for pages/articles that were published once but have been unpublished

            return;
        }

        $templateStructure = $this->structureManager->getStructure($document->getStructureType(), $this->getStructureType());

        foreach ($templateStructure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (!$contentType instanceof ReferenceContentTypeInterface) {
                continue;
            }

            $propertyValue = $structure->getProperty($property->getName());
            $property->setValue($propertyValue->getValue());

            $contentType->getReferences($property, $referenceCollector);
        }

        if ($document instanceof ExtensionBehavior) {
            $extensionData = $document->getExtensionsData();

            if ($extensionData instanceof ExtensionContainer) {
                $extensionData = $extensionData->toArray();
            }

            foreach ($extensionData as $key => $value) {
                $extension = $this->extensionManager->getExtension($templateStructure->getKey(), $key);

                if (!$extension instanceof ReferenceExtensionInterface) {
                    continue;
                }

                $extension->getReferences($value, $referenceCollector, $key . '.');
            }
        }

        $referenceCollector->persistReferences();
    }

    public function removeReferences(UuidBehavior $document, ?string $locale, string $context): void
    {
        $this->referenceRepository->removeBy(\array_filter([
            'referenceResourceKey' => $this->getReferenceResourceKey($document),
            'referenceResourceId' => $document->getUuid(),
            'referenceLocale' => $locale,
            'referenceContext' => $context,
        ]));
    }

    /**
     * @param UuidBehavior&TitleBehavior&StructureBehavior $document
     *
     * @return array<string, string>
     */
    protected function getReferenceRouterAttributes($document, string $locale): array
    {
        return [
            'locale' => $locale,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    private function getReferenceResourceKey(UuidBehavior $document): string
    {
        if (\defined(\get_class($document) . '::RESOURCE_KEY')) {
            return $document::RESOURCE_KEY; // @phpstan-ignore-line PHPStan does not detect the `defined` call
        }

        throw new \RuntimeException('ReferenceResourceKey must be defined');
    }

    private function getStructureType(): string
    {
        return $this->structureType;
    }
}
