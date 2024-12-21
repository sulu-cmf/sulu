<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedLastModifiedBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;

/**
 * Class that "resolves" the view data for a given structure.
 */
class StructureResolver implements StructureResolverInterface
{
    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var bool
     */
    private $enabledTwigAttributes = true;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        ExtensionManagerInterface $structureManager,
        array $enabledTwigAttributes = [
            'path' => true,
        ]
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->extensionManager = $structureManager;
        $this->enabledTwigAttributes = $enabledTwigAttributes;

        if ($enabledTwigAttributes['path'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');
        }
    }

    public function resolve(StructureInterface $structure, bool $loadExcerpt = true/*, array $includedProperties = null*/)
    {
        $includedProperties = (\func_num_args() > 2) ? \func_get_arg(2) : null;

        $data = [
            'view' => [],
            'content' => [],
            'id' => $structure->getUuid(),
            'uuid' => $structure->getUuid(),
            'creator' => $structure->getCreator(),
            'changer' => $structure->getChanger(),
            'created' => $structure->getCreated(),
            'changed' => $structure->getChanged(),
            'template' => $structure->getKey(),
        ];

        if ($this->enabledTwigAttributes['path'] ?? true) {
            $data['path'] = $structure->getPath();
        }

        $document = $structure->getDocument();
        if ($document instanceof ExtensionBehavior && $loadExcerpt) {
            $extensionData = null;
            if (\method_exists($structure, 'getExt')) {
                // BC Layer for old behaviour
                $extensionData = $structure->getExt();
            }

            if (!$extensionData) {
                $extensionData = $document->getExtensionsData();
            }

            // Not in all cases you get a ExtensionContainer as setExtensionData is also called with array only
            if ($extensionData instanceof ExtensionContainer) {
                $extensionData = $extensionData->toArray();
            }

            $data['extension'] = $extensionData ? $extensionData : [];
            foreach ($data['extension'] as $name => $value) {
                $extension = $this->extensionManager->getExtension($structure->getKey(), $name);
                $data['extension'][$name] = $extension->getContentData($value);
            }
        }

        if ($structure instanceof PageBridge) {
            $data['urls'] = $structure->getUrls();
            $data['published'] = $structure->getPublished();
            $data['shadowBaseLocale'] = $structure->getShadowBaseLanguage();
            $data['webspaceKey'] = $structure->getWebspaceKey();
        }

        if ($document instanceof LocalizedLastModifiedBehavior) {
            $data['lastModified'] = $document->getLastModifiedEnabled() ? $document->getLastModified() : null;
        }

        if ($document instanceof LocalizedAuthorBehavior) {
            $data['authored'] = $document->getAuthored();
            $data['author'] = $document->getAuthor();
        }

        // pre-resolve content-types
        foreach ($structure->getProperties(true) as $property) {
            if (null === $includedProperties || \in_array($property->getName(), $includedProperties)) {
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());

                if ($contentType instanceof PreResolvableContentTypeInterface) {
                    $contentType->preResolve($property);
                }
            }
        }

        foreach ($structure->getProperties(true) as $property) {
            if (null === $includedProperties || \in_array($property->getName(), $includedProperties)) {
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                $data['view'][$property->getName()] = $contentType->getViewData($property);
                $data['content'][$property->getName()] = $contentType->getContentData($property);
            }
        }

        return $data;
    }
}
