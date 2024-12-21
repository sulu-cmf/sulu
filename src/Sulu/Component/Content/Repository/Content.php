<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use PHPCR\Query\RowInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Exception\FeatureNotImplementedException;

/**
 * Container class for content data.
 */
#[ExclusionPolicy('all')]
class Content implements \ArrayAccess
{
    /**
     * @var string
     */
    #[Expose]
    private $locale;

    /**
     * @var string
     */
    #[Expose]
    private $webspaceKey;

    /**
     * @var string
     */
    #[Expose]
    private $id;

    /**
     * @var string
     */
    #[Expose]
    private $path;

    /**
     * @var int
     */
    private $workflowStage;

    /**
     * @var int
     */
    private $nodeType;

    /**
     * @var bool
     */
    #[Expose]
    private $hasChildren;

    /**
     * @var string
     */
    private $template;

    /**
     * @var bool
     */
    private $brokenTemplate;

    /**
     * @var Content[]
     */
    private $children;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $permissions;

    /**
     * @var StructureType
     */
    private $localizationType;

    /**
     * @var string
     */
    #[Expose]
    private $url;

    /**
     * @var array<string, string|null>
     */
    private $urls;

    /**
     * @var string[]
     */
    #[Expose]
    private $contentLocales;

    /**
     * @var RowInterface
     */
    private $row;

    public function __construct(
        $locale,
        $webspaceKey,
        $id,
        $path,
        $workflowStage,
        $nodeType,
        $hasChildren,
        $template,
        array $data,
        array $permissions,
        ?StructureType $localizationType = null
    ) {
        $this->locale = $locale;
        $this->webspaceKey = $webspaceKey;
        $this->id = $id;
        $this->path = $path;
        $this->workflowStage = $workflowStage;
        $this->nodeType = $nodeType;
        $this->hasChildren = $hasChildren;
        $this->template = $template;
        $this->data = $data;
        $this->permissions = $permissions;
        $this->localizationType = $localizationType;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns value for given property or given default.
     *
     * @param string $name
     */
    public function getPropertyWithDefault($name, $default = null)
    {
        if (!\array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }

    /**
     * @param string $propertyName
     */
    public function setDataProperty($propertyName, $value)
    {
        $this->data[$propertyName] = $value;
    }

    /**
     * @return int
     */
    public function getWorkflowStage()
    {
        return $this->workflowStage;
    }

    /**
     * @return int
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Returns template.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('template')]
    public function getTemplate()
    {
        if ($this->brokenTemplate) {
            return;
        }

        return $this->template;
    }

    /**
     * Returns original-template.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('originalTemplate')]
    public function getOriginalTemplate()
    {
        return $this->template;
    }

    /**
     * Set broken-template flag.
     *
     * @return $this
     */
    public function setBrokenTemplate()
    {
        $this->brokenTemplate = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBrokenTemplate()
    {
        return $this->brokenTemplate;
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->hasChildren;
    }

    /**
     * @return StructureType
     */
    public function getLocalizationType()
    {
        return $this->localizationType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * @param Content[] $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * @return Content[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return RowInterface
     */
    public function getRow()
    {
        return $this->row;
    }

    public function setRow(RowInterface $row)
    {
        $this->row = $row;
    }

    /**
     * @return string[]
     */
    public function getMapping()
    {
        return \implode(',', \array_keys($this->data));
    }

    /**
     * @return array<string, string|null>
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param array<string, string|null> $urls
     */
    public function setUrls(array $urls)
    {
        $this->urls = $urls;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string[]
     */
    public function getContentLocales()
    {
        return $this->contentLocales;
    }

    /**
     * @param string[] $contentLocales
     */
    public function setContentLocales($contentLocales)
    {
        $this->contentLocales = $contentLocales;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->data);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new FeatureNotImplementedException();
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * @internal
     */
    #[VirtualProperty]
    #[SerializedName('_embedded')]
    public function getEmbedded(): array
    {
        return [
            'pages' => $this->getChildren(),
        ];
    }
}
