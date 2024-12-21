<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;

use Imagine\Image\ImageInterface;
use Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\InvalidMediaFormatException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Abstract xml loader for the image formats which contains the common part of all versions.
 */
abstract class BaseXmlFormatLoader extends FileLoader
{
    public const XML_NAMESPACE_URI = 'http://schemas.sulu.io/media/formats';

    public const SCHEMA_URI = '';

    public const SCHEME_PATH = '';

    public const SCALE_MODE_DEFAULT = ImageInterface::THUMBNAIL_OUTBOUND;

    public const SCALE_RETINA_DEFAULT = false;

    public const SCALE_FORCE_RATIO_DEFAULT = true;

    /**
     * @var \DOMXPath
     */
    protected $xpath;

    /**
     * @var array
     */
    private $globalOptions = [];

    /**
     * @return array
     */
    public function getGlobalOptions()
    {
        return $this->globalOptions;
    }

    /**
     * @param array $globalOptions
     */
    public function setGlobalOptions($globalOptions)
    {
        $this->globalOptions = $globalOptions;
    }

    /**
     * Load formats from a xml file.
     *
     * @param mixed $resource The resource
     * @param string $type The resource type
     *
     * @return array The formats array for the given resource
     */
    public function load($resource, $type = null): mixed
    {
        $path = $this->getLocator()->locate($resource);

        return $this->parseXml($path);
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        if (!\is_string($resource) || 'xml' !== \pathinfo($resource, \PATHINFO_EXTENSION)) {
            return false;
        }

        $file = $this->getLocator()->locate($resource);
        $document = XmlUtils::loadFile($file);
        $namespaces = $document->documentElement->attributes->getNamedItem('schemaLocation')->nodeValue;

        $start = \strpos($namespaces, static::XML_NAMESPACE_URI) + \strlen(static::XML_NAMESPACE_URI) + 1;
        $namespace = \substr($namespaces, $start);

        $end = \strpos($namespace, ' ');
        if (false !== $end) {
            $namespace = \substr($namespace, 0, $end);
        }

        return $namespace === static::SCHEMA_URI;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function parseXml($file)
    {
        $formats = [];

        // load xml file
        $xmlDoc = $this->tryLoad($file);

        $this->xpath = new \DOMXPath($xmlDoc);
        $this->xpath->registerNamespace('x', static::XML_NAMESPACE_URI);

        foreach ($this->xpath->query('/x:formats/x:format') as $formatNode) {
            $this->addFormatFromFormatNode($formatNode, $formats);
        }

        return $formats;
    }

    /**
     * For a given format node and a given array of formats, this method parses the
     * format node to an array and adds it to the formats array.
     *
     * @param array $formats
     */
    private function addFormatFromFormatNode(\DOMNode $formatNode, &$formats)
    {
        $key = $this->getKeyFromFormatNode($formatNode);
        $internal = $this->getInternalFlagFromFormatNode($formatNode);

        $meta = $this->getMetaFromFormatNode($formatNode);
        $scale = $this->getScaleFromFormatNode($formatNode);
        $transformations = $this->getTransformationsFromFormatNode($formatNode);
        $options = $this->getOptionsFromFormatNode($formatNode);

        $formats[$key] = [
            'key' => $key,
            'internal' => $internal,
            'meta' => $meta,
            'scale' => $scale,
            'transformations' => $transformations,
            'options' => \array_merge($this->globalOptions, $options),
        ];
    }

    /**
     * Tries to load the DOM Document of a given image formats xml.
     *
     * @param string $file The path to the xml file
     *
     * @return \DOMDocument
     *
     * @throws InvalidMediaFormatException
     */
    private function tryLoad($file)
    {
        try {
            return XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidMediaFormatException(
                \sprintf('Could not parse image formats XML file "%s"', $file),
                null,
                $e
            );
        }
    }

    /**
     * @internal
     */
    protected function getMode($modeNode)
    {
        if (!$modeNode) {
            return static::SCALE_MODE_DEFAULT;
        }

        $mode = $modeNode->nodeValue;

        if ('outbound' === $mode) {
            $mode = ImageInterface::THUMBNAIL_OUTBOUND;
        } elseif ('inset' === $mode) {
            $mode = ImageInterface::THUMBNAIL_INSET;
        } else {
            throw new InvalidMediaFormatException(\sprintf('The scale mode "%s" is not supported', $mode));
        }

        return $mode;
    }

    /**
     * For a given dom node returns an array of parameters. The xml name of the parameter
     * tag can be passed as an argument.
     *
     * @param \DOMNode $node
     * @param string $parameterName
     *
     * @return array
     */
    protected function getParametersFromNode($node, $parameterName = 'parameter')
    {
        if (null === $node) {
            return [];
        }

        $parameters = [];
        foreach ($this->xpath->query('x:' . $parameterName, $node) as $parameterNode) {
            $name = $this->xpath->query('@name', $parameterNode)->item(0)->nodeValue;
            $parameters[$name] = $parameterNode->nodeValue;
        }

        return $parameters;
    }

    /**
     * For a given format node returns the key of the format.
     *
     * @return string
     */
    abstract protected function getKeyFromFormatNode(\DOMNode $formatNode);

    /**
     * For a given format node returns the internal flag of the format.
     *
     * @return bool
     */
    abstract protected function getInternalFlagFromFormatNode(\DOMNode $formatNode);

    /**
     * For a given format node returns the meta information of the format.
     *
     * @return array
     */
    abstract protected function getMetaFromFormatNode(\DOMNode $formatNode);

    /**
     * For a given format node returns the scale information of the format.
     *
     * @return array
     */
    abstract protected function getScaleFromFormatNode(\DOMNode $formatNode);

    /**
     * For a given format node returns the transformations for it.
     *
     * @return array
     */
    abstract protected function getTransformationsFromFormatNode(\DOMNode $formatNode);

    /**
     * For a given format node returns the options for it.
     *
     * @return array
     */
    private function getOptionsFromFormatNode(\DOMNode $formatNode)
    {
        $optionsNode = $this->xpath->query('x:options', $formatNode)->item(0);

        return $this->getParametersFromNode($optionsNode, 'option');
    }
}
