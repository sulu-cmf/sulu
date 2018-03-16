<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader;

use Exception;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * reads a template xml and returns a array representation.
 */
abstract class AbstractLoader implements LoaderInterface
{
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
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $schemaPath = __DIR__ . $this->schemaPath;

        $cwd = getcwd();
        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @chdir(dirname($resource));

        // read file
        $xmlDocument = XmlUtils::loadFile(
            $resource,
            function (\DOMDocument $dom) use ($resource, $schemaPath) {
                $dom->documentURI = $resource;
                $dom->xinclude();

                return @$dom->schemaValidate($schemaPath);
            }
        );

        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @chdir($cwd);

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
     * @param $path
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
                if (in_array($key, ['name'])) {
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
     * @param $path
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
                if (in_array($key, ['key'])) {
                    $area[$key] = $attr->value;
                } else {
                    $area['attributes'][$key] = $attr->value;
                }
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

    protected function loadMeta($path, \DOMXPath $xpath, \DOMNode $context = null)
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

    /**
     * returns value of path.
     */
    protected function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        try {
            $result = $xpath->query($path, $context);
            if (0 === $result->length) {
                return $default;
            }

            $item = $result->item(0);
            if (null === $item) {
                return $default;
            }

            if ('true' === $item->nodeValue) {
                return true;
            }

            if ('false' === $item->nodeValue) {
                return false;
            }

            return $item->nodeValue;
        } catch (Exception $ex) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }
}
