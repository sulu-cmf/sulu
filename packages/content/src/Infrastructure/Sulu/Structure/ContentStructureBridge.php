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

namespace Sulu\Content\Infrastructure\Sulu\Structure;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class ContentStructureBridge implements StructureInterface, RoutableStructureInterface
{
    /**
     * @var StructureMetadata
     */
    protected $structure;

    /**
     * @var LegacyPropertyFactory
     */
    private $propertyFactory;

    /**
     * @var TemplateInterface
     */
    protected $content;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var array<string|int|float, PropertyInterface>
     */
    private $loadedProperties = [];

    /**
     * @param string|int $id
     */
    public function __construct(
        StructureMetadata $structure,
        LegacyPropertyFactory $propertyFactory,
        TemplateInterface $content,
        $id,
        string $locale
    ) {
        $this->structure = $structure;
        $this->propertyFactory = $propertyFactory;
        $this->content = $content;
        $this->id = $id;
        $this->locale = $locale;
    }

    public function getDocument(): ContentDocument
    {
        return new ContentDocument($this->content, $this->locale);
    }

    public function getStructure(): StructureMetadata
    {
        return $this->structure;
    }

    public function getContent(): TemplateInterface
    {
        return $this->content;
    }

    public function setLanguageCode($locale): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getLanguageCode(): string
    {
        return $this->locale;
    }

    public function setWebspaceKey($webspace): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getWebspaceKey(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return (string) $this->id;
    }

    public function setUuid($uuid): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getView(): ?string
    {
        return $this->structure->getView();
    }

    public function getController(): ?string
    {
        return $this->structure->getController();
    }

    /**
     * @return array{
     *     type: string,
     *     value: string,
     * }
     */
    public function getCacheLifeTime(): array
    {
        /** @var array{
         *     type: string,
         *     value: string,
         * } */
        return $this->structure->getCacheLifetime();
    }

    public function getCreator(): ?int
    {
        return null;
    }

    public function setCreator($userId): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getChanger(): ?int
    {
        return null;
    }

    public function setChanger($userId): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return null;
    }

    public function setCreated(\DateTime $created): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getChanged(): ?\DateTimeInterface
    {
        return null;
    }

    public function setChanged(\DateTime $changed): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getKey()
    {
        /** @var string $key */
        $key = $this->structure->getName();

        return $key;
    }

    public function getProperty($name)
    {
        if ($this->hasProperty($name)) {
            $property = $this->structure->getProperty($name);
        } else {
            $property = $this->structure->getChild($name);
        }

        return $this->createLegacyPropertyFromItem($property);
    }

    public function hasProperty($name)
    {
        return $this->structure->hasProperty($name);
    }

    public function getProperties($flatten = false)
    {
        if ($flatten) {
            $items = $this->structure->getProperties();
        } else {
            $items = $this->structure->getChildren();
        }

        $propertyBridges = [];
        foreach ($items as $property) {
            $propertyBridges[$property->getName()] = $this->createLegacyPropertyFromItem($property);
        }

        return $propertyBridges;
    }

    public function setHasChildren($hasChildren): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getHasChildren(): bool
    {
        return false;
    }

    public function setChildren($children): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getChildren(): array
    {
        return [];
    }

    public function getPublishedState(): bool
    {
        return true;
    }

    public function setPublished($published): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getPublished(): ?\DateTimeInterface
    {
        return null;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getPropertyValue($name)
    {
        return $this->getProperty($name)->getValue();
    }

    /**
     * @return string[]
     */
    public function getPropertyNames()
    {
        return \array_keys($this->structure->getChildren());
    }

    public function setType($type): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getType(): ?StructureType
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return null;
    }

    public function setPath($path): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function setHasTranslation($hasTranslation): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getHasTranslation(): bool
    {
        return true;
    }

    /**
     * @param bool $complete
     *
     * @return mixed[]
     */
    public function toArray($complete = true): array
    {
        return [];
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return $this->toArray(true);
    }

    public function getPropertyByTagName($tagName, $highest = true)
    {
        return $this->createLegacyPropertyFromItem($this->structure->getPropertyByTagName($tagName, $highest));
    }

    public function getPropertiesByTagName($tagName)
    {
        $properties = [];
        foreach ($this->structure->getPropertiesByTagName($tagName) as $structureProperty) {
            $properties[] = $this->createLegacyPropertyFromItem($structureProperty);
        }

        return $properties;
    }

    /**
     * @param string $tagName
     *
     * @return mixed
     */
    public function getPropertyValueByTagName($tagName)
    {
        return $this->getPropertyByTagName($tagName)->getValue();
    }

    public function hasTag($tag): bool
    {
        return $this->structure->hasPropertyWithTagName($tag);
    }

    public function getNodeType(): int
    {
        return RedirectType::NONE;
    }

    public function getNodeName(): ?string
    {
        return null;
    }

    public function getLocalizedTitle($languageCode): ?string
    {
        return null;
    }

    public function getNodeState(): int
    {
        return WorkflowStage::PUBLISHED;
    }

    public function copyFrom(StructureInterface $structure): void
    {
        throw $this->createReadOnlyException(__METHOD__);
    }

    public function getInternal(): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function getNavContexts(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getShadowLocales(): array
    {
        $content = $this->getContent();
        if ($content instanceof ShadowInterface) {
            return $content->getShadowLocales() ?? [];
        }

        return [];
    }

    public function getShadowBaseLanguage(): ?string
    {
        $content = $this->getContent();
        if ($content instanceof ShadowInterface) {
            return $content->getShadowLocale();
        }

        return null;
    }

    public function getIsShadow(): bool
    {
        $content = $this->getContent();
        if ($content instanceof ShadowInterface) {
            return (bool) $content->getShadowLocale();
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getContentLocales(): array
    {
        $content = $this->getContent();

        if ($content instanceof DimensionContentInterface) {
            return $content->getAvailableLocales() ?? [];
        }

        return [];
    }

    public function getOriginTemplate(): ?string
    {
        return null;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getProperty($name)->getValue();
    }

    protected function createReadOnlyException(string $method): \BadMethodCallException
    {
        return new \BadMethodCallException(
            \sprintf(
                'Compatibility layer StructureBridge instances are readonly. Tried to call "%s"',
                $method
            )
        );
    }

    /**
     * @param PropertyMetadata|ItemMetadata $item
     */
    private function createLegacyPropertyFromItem(ItemMetadata $item): PropertyInterface
    {
        $name = $item->getName();
        if (isset($this->loadedProperties[$name])) {
            return $this->loadedProperties[$name];
        }

        /** @var Property $propertyBridge */
        $propertyBridge = $this->propertyFactory->createProperty($item, $this);
        $property = new PropertyValue($name, $this->content->getTemplateData()[$name] ?? null);
        $propertyBridge->setPropertyValue($property);

        $this->loadedProperties[$name] = $propertyBridge;

        return $propertyBridge;
    }
}
