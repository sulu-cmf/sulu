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

use Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\MissingScaleDimensionException;

/**
 * Class XmlFormatLoader for the version 1.1 of the image-formats.
 *
 * @deprecated
 */
class XmlFormatLoader10 extends BaseXmlFormatLoader
{
    public const SCHEMA_URI = 'http://schemas.sulu.io/media/formats-1.0.xsd';

    public const SCHEME_PATH = '/schema/formats/formats-1.0.xsd';

    public function load($resource, $type = null): mixed
    {
        @trigger_deprecation(
            'sulu/sulu',
            '1.4',
            '%s is deprecated and will be removed in 2.0. Use XmlFormatLoader11 instead.',
            __CLASS__
        );

        return parent::load($resource, $type);
    }

    protected function getKeyFromFormatNode(\DOMNode $formatNode)
    {
        return $this->xpath->query('x:name', $formatNode)->item(0)->nodeValue;
    }

    protected function getInternalFlagFromFormatNode(\DOMNode $formatNode)
    {
        return false;
    }

    protected function getMetaFromFormatNode(\DOMNode $formatNode)
    {
        return [
            'title' => [],
        ];
    }

    protected function getScaleFromFormatNode(\DOMNode $formatNode)
    {
        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ('scale' === $action || 'resize' === $action) {
                $xNode = $this->xpath->query('x:parameters/x:parameter[@name = "x"]', $commandNode)->item(0);
                $yNode = $this->xpath->query('x:parameters/x:parameter[@name = "y"]', $commandNode)->item(0);
                $modeNode = $this->xpath->query('x:parameters/x:parameter[@name = "mode"]', $commandNode)->item(0);
                $retinaNode = $this->xpath->query('x:parameters/x:parameter[@name = "retina"]', $commandNode)->item(0);
                $forceRatioNode = $this->xpath->query(
                    'x:parameters/x:parameter[@name = "forceRatio"]',
                    $commandNode
                )->item(0);

                $xValue = null;
                $yValue = null;
                $forceRatio = static::SCALE_FORCE_RATIO_DEFAULT;
                $retina = static::SCALE_RETINA_DEFAULT;
                if (null !== $xNode && '' !== $xNode->nodeValue) {
                    $xValue = \intval($xNode->nodeValue);
                }
                if (null !== $yNode && '' !== $yNode->nodeValue) {
                    $yValue = \intval($yNode->nodeValue);
                }
                if (null === $xValue && null === $yValue) {
                    throw new MissingScaleDimensionException();
                }

                if (null !== $forceRatioNode && 'false' === $forceRatioNode->nodeValue) {
                    $forceRatio = false;
                }
                if (null !== $retinaNode && 'true' === $retinaNode->nodeValue) {
                    $retina = true;
                }

                return [
                    'x' => $xValue,
                    'y' => $yValue,
                    'mode' => $this->getMode($modeNode),
                    'retina' => $retina,
                    'forceRatio' => $forceRatio,
                ];
            }
        }

        return;
    }

    protected function getTransformationsFromFormatNode(\DOMNode $formatNode)
    {
        $transformations = [];

        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ('scale' === $action || 'resize' === $action) {
                continue;
            }

            $actionNode = $this->xpath->query('x:action', $commandNode)->item(0);
            $parametersNode = $this->xpath->query('x:parameters', $commandNode)->item(0);

            $transformations[] = [
                'effect' => $actionNode->nodeValue,
                'parameters' => $this->getParametersFromNode($parametersNode),
            ];
        }

        return $transformations;
    }
}
