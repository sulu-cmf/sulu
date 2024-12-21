<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Utility class for extracting Sulu-centric properties from nodes.
 * Note this should be removed when we have domain objects.
 */
class SuluNodeHelper
{
    /**
     * @param string $languageNamespace
     * @param array<string, string|null> $paths Path segments from configuration
     */
    public function __construct(
        private SessionInterface $session,
        private $languageNamespace,
        private $paths,
        private StructureMetadataFactoryInterface $structureMetadataFactory
    ) {
        $this->paths = \array_merge(
            [
                'base' => null,
                'content' => null,
                'route' => null,
                'snippet' => null,
            ],
            $paths
        );
    }

    /**
     * Return the languages that are currently registered on the
     * given PHPCR node.
     *
     * @return array
     */
    public function getLanguagesForNode(NodeInterface $node)
    {
        $languages = [];
        foreach ($node->getProperties() as $property) {
            /* @var PropertyInterface $property */
            \preg_match('/^' . $this->languageNamespace . ':([a-zA-Z_]*?)-changer/', $property->getName(), $matches);

            if ($matches) {
                $languages[$matches[1]] = $matches[1];
            }
        }

        return \array_values($languages);
    }

    /**
     * Return the structure type for the given node.
     *
     * @return string
     */
    public function getStructureTypeForNode(NodeInterface $node)
    {
        $mixinTypes = $node->getPropertyValueWithDefault('jcr:mixinTypes', []);

        if (\in_array('sulu:' . Structure::TYPE_PAGE, $mixinTypes)) {
            return Structure::TYPE_PAGE;
        }

        if (\in_array('sulu:' . Structure::TYPE_SNIPPET, $mixinTypes)) {
            return Structure::TYPE_SNIPPET;
        }

        return;
    }

    /**
     * Return all the localized values of the localized property indicated
     * by $name.
     *
     * @param string $name Name of localized property
     */
    public function getLocalizedPropertyValues(NodeInterface $node, $name)
    {
        $values = [];
        foreach ($node->getProperties() as $property) {
            /* @var PropertyInterface $property */
            \preg_match('/^' . $this->languageNamespace . ':([a-zA-Z_]*?)-' . $name . '/', $property->getName(), $matches);

            if ($matches) {
                $values[$matches[1]] = $property->getValue();
            }
        }

        return $values;
    }

    /**
     * Return true if the given node has the given
     * nodeType property (or properties).
     *
     * The sulu node type is the local name of node types
     * with the sulu namespace.
     *
     * Example:
     *   sulu:snippet is the PHPCR node type
     *   snippet is the Sulu node type
     *
     * @param NodeInterface $node
     * @param string|array $suluNodeTypes One or more node sulu types
     *
     * @return bool
     */
    public function hasSuluNodeType($node, $suluNodeTypes)
    {
        foreach ((array) $suluNodeTypes as $suluNodeType) {
            if (\in_array($suluNodeType, $node->getPropertyValueWithDefault('jcr:mixinTypes', []))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts webspace key from given path.
     *
     * TODO: We should inject the base path here
     *
     * @param string $path path of node
     *
     * @return string
     */
    public function extractWebspaceFromPath($path)
    {
        $match = \preg_match('/^\/' . $this->getPath('base') . '\/([^\/]*)\/.*$/', $path, $matches);

        if ($match) {
            return $matches[1];
        } else {
            return;
        }
    }

    /**
     * Returns the path for the base snippet of the given type resp. of all types if not given.
     *
     * @param string $type
     *
     * @return string
     */
    public function getBaseSnippetPath($type = null)
    {
        $path = '/' . $this->getPath('base') . '/' . $this->getPath('snippet');

        if ($type) {
            $path .= '/' . $type;
        }

        return $path;
    }

    /**
     * Returns the uuid for the base snippet of the given type resp. of all types if not given.
     *
     * @param string $type
     *
     * @return string
     */
    public function getBaseSnippetUuid($type = null)
    {
        try {
            return $this->session->getNode($this->getBaseSnippetPath($type))->getIdentifier();
        } catch (PathNotFoundException $e) {
            $snippetStructures = \array_map(function(StructureMetadata $structureMetadata) {
                return $structureMetadata->getName();
            }, $this->structureMetadataFactory->getStructures('snippet'));

            if (\in_array($type, $snippetStructures)) {
                return null;
            }

            throw new \InvalidArgumentException(\sprintf(
                'Snippet type "%s" not available, available snippet types are: [%s]',
                $type,
                \implode(', ', $snippetStructures)
            ), 0, $e);
        }
    }

    /**
     * Extract the snippet path from the given path.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function extractSnippetTypeFromPath($path)
    {
        if ('/' !== \substr($path, 0, 1)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Path must be absolute, got "%s"',
                    $path
                )
            );
        }

        $snippetsPath = $this->getBaseSnippetPath() . '/';
        $newPath = PathHelper::getParentPath($path);
        $newPath = \substr($newPath, \strlen($snippetsPath));

        // $newPath can be false or empty because of return difference of substr depending on php version (<= 7.4, 8.0)
        if (!$newPath) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Cannot extract snippet template type from path "%s"',
                    $path
                )
            );
        }

        return $newPath;
    }

    /**
     * Return the next node of the given node.
     *
     * @see getSiblingNode
     */
    public function getNextNode(NodeInterface $node)
    {
        return $this->getSiblingNode($node);
    }

    /**
     * Return the previous node of the given node.
     *
     * @see getSiblingNode
     */
    public function getPreviousNode(NodeInterface $node)
    {
        return $this->getSiblingNode($node, true);
    }

    /**
     * Return translated property name.
     *
     * @param string $propertyName
     * @param string $locale
     *
     * @return string
     */
    public function getTranslatedPropertyName($propertyName, $locale)
    {
        return \sprintf('%s:%s-%s', $this->languageNamespace, $locale, $propertyName);
    }

    /**
     * Return translated property.
     *
     * @param \Sulu\Component\Content\Compat\PropertyInterface $property
     * @param string $locale
     * @param string $prefix
     *
     * @return \Sulu\Component\Content\Compat\PropertyInterface
     */
    public function getTranslatedProperty($property, $locale, $prefix = null)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace, $prefix);
    }

    /**
     * Return either the next or previous sibling of the given node
     * according to the $previous flag.
     *
     * @return NodeInterface|null
     *
     * @throws \RuntimeException
     */
    private function getSiblingNode(NodeInterface $node, bool $previous = false)
    {
        $parentNode = $node->getParent();
        $children = $parentNode->getNodes();
        $previousNode = null;

        while ($child = $children->current()) {
            if ($child->getPath() === $node->getPath()) {
                if ($previous) {
                    return $previousNode;
                }
                $children->next();

                return $children->current();
            }

            $previousNode = $child;
            $children->next();
        }

        throw new \RuntimeException(
            \sprintf(
                'Could not find node with path "%s" as a child of "%s". This should not happen',
                $node->getPath(),
                $parentNode->getPath()
            )
        );
    }

    /**
     * Return the configured named path segment.
     *
     * @param string $name Name of path segment
     *
     * @return string The path segment
     *
     * @throws \InvalidArgumentException
     */
    private function getPath($name)
    {
        if (!isset($this->paths[$name])) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Unknown path segment name "%s", known paths are "%s"',
                    $name,
                    \implode('", "', \array_keys($this->paths))
                )
            );
        }

        $name = $this->paths[$name];

        return $name;
    }
}
