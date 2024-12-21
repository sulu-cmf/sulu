<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader;

use Sulu\Component\Content\Metadata\XmlParserTrait;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * reads a template xml and returns a array representation.
 */
abstract class AbstractLoader implements LoaderInterface
{
    use XmlParserTrait;

    /**
     * @var string
     */
    protected $schemaPath;

    /**
     * @var string
     */
    protected $schemaNamespaceURI;

    public function __construct(
        string $schemaPath,
        string $schemaNamespaceURI
    ) {
        $this->schemaPath = $schemaPath;
        $this->schemaNamespaceURI = $schemaNamespaceURI;
    }

    /**
     * @param string $resource
     */
    public function load($resource, $type = null): mixed
    {
        $schemaPath = __DIR__ . $this->schemaPath;

        $cwd = \getcwd();
        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @\chdir(\dirname($resource));

        // read file
        $xmlDocument = XmlUtils::loadFile(
            $resource,
            function(\DOMDocument $dom) use ($resource, $schemaPath) {
                $dom->documentURI = $resource;
                $dom->xinclude();

                return @$dom->schemaValidate($schemaPath);
            }
        );

        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @\chdir($cwd);

        // generate xpath for file
        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', $this->schemaNamespaceURI);

        // init result
        $result = $this->parse($resource, $xpath, $type);

        return $result;
    }

    abstract protected function parse($resource, \DOMXPath $xpath, $type);

    /**
     * Loads the tags for the structure.
     *
     * @param string $path
     * @param \DOMXPath $xpath
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function loadStructureTags($path, $xpath)
    {
        $result = [];

        foreach ($xpath->query($path) as $node) {
            $tag = [
                'name' => null,
                'attributes' => [],
            ];

            foreach ($node->attributes as $key => $attr) {
                if (\in_array($key, ['name'])) {
                    $tag[$key] = $attr->value;
                } else {
                    $tag['attributes'][$key] = $attr->value;
                }
            }

            if (!isset($tag['name'])) {
                // this should not happen because of the XSD validation
                throw new \InvalidArgumentException('Tag does not have a name in template definition');
            }

            $result[] = $tag;
        }

        return $result;
    }

    /**
     * Loads the areas for the structure.
     *
     * @param string $path
     * @param \DOMXPath $xpath
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function loadStructureAreas($path, $xpath)
    {
        $result = [];

        foreach ($xpath->query($path) as $node) {
            $area = [];

            foreach ($node->attributes as $key => $attr) {
                if (\in_array($key, ['key', 'cache-invalidation'])) {
                    $area[$key] = $attr->value;
                } else {
                    $area['attributes'][$key] = $attr->value;
                }
            }

            if (!isset($area['cache-invalidation'])) {
                $area['cache-invalidation'] = 'true';
            }

            $meta = $this->loadMeta('x:meta/x:*', $xpath, $node);
            $area['title'] = $meta['title'];

            if (!isset($area['key'])) {
                // this should not happen because of the XSD validation
                throw new \InvalidArgumentException('Zone does not have a key in the attributes');
            }

            $result[] = $area;
        }

        return $result;
    }

    protected function loadMeta($path, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $attribute = $node->tagName;
            $lang = $this->getValueFromXPath('@lang', $xpath, $node);

            if (!isset($result[$node->tagName])) {
                $result[$attribute] = [];
            }
            $result[$attribute][$lang] = $node->textContent;
        }

        return $result;
    }

    public function supports($resource, $type = null): bool
    {
        throw new FeatureNotImplementedException();
    }

    public function getResolver(): LoaderResolverInterface
    {
        throw new FeatureNotImplementedException();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        throw new FeatureNotImplementedException();
    }
}
