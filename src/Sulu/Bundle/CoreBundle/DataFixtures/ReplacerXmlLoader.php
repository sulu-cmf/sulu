<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DataFixtures;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Reads and parses replacers.xml.
 */
class ReplacerXmlLoader extends FileLoader
{
    /**
     * @param string $resource
     */
    public function load($resource, $type = null): mixed
    {
        $path = $this->getLocator()->locate($resource);

        // load data in path
        return $this->parseXml($path);
    }

    public function supports($resource, $type = null): bool
    {
        return 'xml' === \pathinfo($resource, \PATHINFO_EXTENSION);
    }

    private function parseXml($path)
    {
        // load xml file
        $xmlDoc = XmlUtils::loadFile($path);
        $xpath = new \DOMXPath($xmlDoc);

        $result = [];

        foreach ($xpath->query('/replacers/item') as $node) {
            $locale = \strtolower($xpath->query('column[@name="locale"]', $node)->item(0)->nodeValue);
            $from = $xpath->query('column[@name="from"]', $node)->item(0)->nodeValue;
            $to = $xpath->query('column[@name="to"]', $node)->item(0)->nodeValue;

            if (!isset($result[$locale])) {
                $result[$locale] = [];
            }

            $result[$locale][$from] = $to;
        }

        return $result;
    }
}
