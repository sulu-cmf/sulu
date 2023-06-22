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

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
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

    private DocumentInspector $documentInspector;

    private string $structureType;

    protected string $referenceSecurityContext;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ReferenceRepositoryInterface $referenceRepository,
        DocumentInspector $documentInspector,
        string $structureType,
        string $referenceSecurityContext
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->referenceRepository = $referenceRepository;
        $this->documentInspector = $documentInspector;
        $this->structureType = $structureType;
        $this->referenceSecurityContext = $referenceSecurityContext;
    }

    abstract public static function getResourceKey(): string;

    public function updateReferences($document, string $locale): ReferenceCollectorInterface
    {
        $referenceResourceKey = $this->getReferenceResourceKey($document);

        $workflowStage = $document instanceof WorkflowStageBehavior ? (int) $document->getWorkflowStage() : 0;

        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            $referenceResourceKey,
            $document->getUuid(),
            $locale,
            $document->getTitle(),
            $this->getReferenceViewAttributes($document, $locale),
            $workflowStage
        );

        $structure = $document->getStructure();
        $templateStructure = $this->structureManager->getStructure($document->getStructureType(), $this->getStructureType());

        foreach ($templateStructure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if (!$contentType instanceof ReferenceContentTypeInterface) {
                continue;
            }

            $contentType->getReferences($property, $structure->getProperty($property->getName()), $referenceCollector);
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

        return $referenceCollector;
    }

    public function removeReferences(UuidBehavior $document, ?string $locale = null): void
    {
        $locales = $locale ? [$locale] : $this->documentInspector->getLocales($document);

        foreach ($locales as $locale) {
            $this->referenceRepository->removeBy([
                'referenceResourceKey' => $this->getReferenceResourceKey($document),
                'referenceResourceId' => $document->getUuid(),
                'locale' => $locale,
            ]);
        }
    }

    /**
     * @param UuidBehavior&TitleBehavior&StructureBehavior $document
     *
     * @return array<string, string>
     */
    protected function getReferenceViewAttributes($document, string $locale): array
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
