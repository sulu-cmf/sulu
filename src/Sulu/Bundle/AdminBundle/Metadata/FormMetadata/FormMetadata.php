<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\AdminBundle\Metadata\AbstractMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

class FormMetadata extends AbstractMetadata
{
    /**
     * @var string
     *
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    private $name;

    /**
     * @var string
     *
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    private $title;

    /**
     * @var ItemMetadata[]
     *
     * @SerializedName("form")
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    private $items;

    /**
     * @var SchemaMetadata
     *
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    private $schema;

    /**
     * @var string
     *
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    private $key;

    /**
     * @var TagMetadata[]
     *
     * @Exclude(if="'admin_form_metadata_keys_only' in context.getAttribute('groups')")
     */
    protected $tags;

    public function __construct()
    {
        $this->schema = new SchemaMetadata();
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return ItemMetadata[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param ItemMetadata[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }

    public function addItem(ItemMetadata $item): void
    {
        $this->items[$item->getName()] = $item;
    }

    public function setSchema(SchemaMetadata $schema)
    {
        $this->schema = $schema;
    }

    public function getSchema(): SchemaMetadata
    {
        return $this->schema;
    }

    /**
     * @return TagMetadata[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return TagMetadata[]
     */
    public function getTagsByName(string $name): array
    {
        $tags = [];
        foreach ($this->getTags() as $tag) {
            if ($tag->getName() === $name) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    public function addTag(TagMetadata $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * @param TagMetadata[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function merge(self $otherForm): FormMetadata
    {
        $mergedForm = new self();
        $mergedForm->setKey($this->getKey());
        if ($this->name) {
            $mergedForm->setName($this->name);
        }
        if ($this->title) {
            $mergedForm->setTitle($this->title);
        }

        $mergedForm->setTags(\array_merge($this->getTags(), $otherForm->getTags()));
        $mergedForm->setItems(\array_merge($this->getItems(), $otherForm->getItems()));
        $mergedForm->setSchema($this->getSchema()->merge($otherForm->getSchema()));

        return $mergedForm;
    }
}
