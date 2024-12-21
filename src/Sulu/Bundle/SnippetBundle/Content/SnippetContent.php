<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Util\UUIDHelper;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;

class SnippetContent extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface, ReferenceContentTypeInterface
{
    /**
     * @param bool $defaultEnabled
     */
    public function __construct(
        private DefaultSnippetManagerInterface $defaultSnippetManager,
        private SnippetResolverInterface $snippetResolver,
        private ReferenceStoreInterface $referenceStore,
        protected $defaultEnabled,
        private ?ReferenceStoreInterface $snippetAreaReferenceStore = null,
    ) {
        if (null === $this->snippetAreaReferenceStore) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.6',
                'Instantiating the SnippetContent without the $snippetAreaReferenceStore argument is deprecated!'
            );
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $refs = [];
        if ($node->hasProperty($property->getName())) {
            $refs = $node->getProperty($property->getName())->getString();
        }

        $property->setValue($refs);
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $values = $property->getValue();

        $snippetReferences = [];
        $values = \is_array($values) ? $values : [];

        foreach ($values as $value) {
            if ($value instanceof SnippetBridge) {
                $snippetReferences[] = $value->getUuid();
            } elseif (\is_array($value) && \array_key_exists('uuid', $value) && UUIDHelper::isUUID($value['uuid'])) {
                $snippetReferences[] = $value['uuid'];
            } elseif (UUIDHelper::isUUID($value)) {
                $snippetReferences[] = $value;
            } else {
                throw new \InvalidArgumentException(
                    \sprintf(
                        'Property value must either be a UUID or a Snippet, "%s" given.',
                        \gettype($value)
                    )
                );
            }
        }

        $node->setProperty($property->getName(), $snippetReferences, PropertyType::REFERENCE);
    }

    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    public function getViewData(PropertyInterface $property)
    {
        $viewData = [];
        foreach ($this->getSnippets($property) as $snippet) {
            $viewData[] = $snippet['view'];
        }

        return $viewData;
    }

    public function getContentData(PropertyInterface $property)
    {
        $contentData = [];
        foreach ($this->getSnippets($property) as $snippet) {
            $contentData[] = $snippet['content'];
        }

        return $contentData;
    }

    /**
     * Returns snippets with given property value.
     */
    private function getSnippets(PropertyInterface $property)
    {
        /** @var PageBridge $page */
        $page = $property->getStructure();
        $webspaceKey = $page->getWebspaceKey();
        $locale = $page->getLanguageCode();
        $shadowLocale = null;
        if ($page->getIsShadow()) {
            $shadowLocale = $page->getShadowBaseLanguage();
        }

        $refs = $property->getValue();
        $ids = $this->getUuids($refs);

        $snippetType = $this->getParameterValue($property->getParams(), 'snippetType');
        $default = $this->getParameterValue($property->getParams(), 'default', false);

        $snippetArea = $default;
        if (true === $snippetArea || 'true' === $snippetArea) {
            $snippetArea = $snippetType;
        }

        if (empty($ids) && $snippetArea && $this->defaultEnabled) {
            $ids = $this->loadSnippetAreaIds($webspaceKey, $snippetArea, $locale);
        }

        $params = $property->getParams();
        $loadExcerpt = isset($params['loadExcerpt']) ? $params['loadExcerpt']->getValue() : false;

        return $this->snippetResolver->resolve($ids, $webspaceKey, $locale, $shadowLocale, $loadExcerpt);
    }

    private function loadSnippetAreaIds($webspaceKey, $snippetArea, $locale)
    {
        try {
            $snippet = $this->defaultSnippetManager->load($webspaceKey, $snippetArea, $locale);
            $this->snippetAreaReferenceStore?->add($snippetArea);
        } catch (WrongSnippetTypeException $exception) {
            return [];
        }

        if (!$snippet) {
            return [];
        }

        return [$snippet->getUuid()];
    }

    /**
     * The data is not always normalized, so we normalize the data here.
     */
    private function getUuids($data)
    {
        return \is_array($data) ? $data : [];
    }

    /**
     * Returns value of parameter.
     * If parameter not exists the default will be returned.
     *
     * @param PropertyParameter[] $parameter
     * @param string $name
     */
    private function getParameterValue(array $parameter, $name, $default = null)
    {
        if (!\array_key_exists($name, $parameter)) {
            return $default;
        }

        return $parameter[$name]->getValue();
    }

    public function exportData($propertyValue)
    {
        $uuids = $this->getUuids($propertyValue);

        if (empty($uuids)) {
            return '';
        }

        return \json_encode($this->getUuids($propertyValue));
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(\json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function preResolve(PropertyInterface $property)
    {
        foreach ($this->getUuids($property->getValue()) as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $data = $property->getValue();
        if (!\is_array($data)) {
            return;
        }

        foreach ($data as $id) {
            if (!\is_string($id)) {
                continue;
            }

            $referenceCollector->addReference(
                SnippetDocument::RESOURCE_KEY,
                $id,
                $propertyPrefix . $property->getName()
            );
        }
    }
}
