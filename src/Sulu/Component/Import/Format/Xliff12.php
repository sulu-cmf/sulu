<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Import\Format;

use Symfony\Component\Config\Util\XmlUtils;

/**
 * Import an xliff 1.2 file.
 */
class Xliff12 implements FormatImportInterface
{
    public function parse($filePath, $locale)
    {
        $dom = XmlUtils::loadFile($filePath);

        return $this->extractData($dom, $locale);
    }

    public function getProperty($name, $data, $contentTypeName = null, $extension = null, $default = null)
    {
        $propertyName = '';

        if ($extension) {
            $propertyName = $extension . '-';
        }

        $propertyName .= $name;

        if (!isset($data[$propertyName])) {
            return $default;
        }

        $property = $data[$propertyName];
        $property['type'] = $contentTypeName;

        return $property;
    }

    public function getPropertyData($name, $data, $contentTypeName = null, $extension = null, $default = null)
    {
        $property = $this->getProperty($name, $data, $contentTypeName, $extension);

        if (!isset($property['children'])) {
            if (!isset($property['value'])) {
                return $default;
            }

            return $property['value'];
        }

        $data = [];
        foreach ($property['children'] as $key => $child) {
            $data[$key] = $this->getChildPropertyDatas($child);
        }

        return $data;
    }

    /**
     * Prepare data for structure.
     *
     * @param mixed[] $child
     *
     * @return mixed[]
     */
    private function getChildPropertyDatas($child)
    {
        $childProperties = [];
        foreach (\array_keys($child) as $childKey) {
            $childProperties[$childKey] = $this->getPropertyData($childKey, $child, $contentTypeName = null, $extension = null);
        }

        return $childProperties;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    protected function extractData(\DOMDocument $dom, $locale)
    {
        $xml = \simplexml_import_dom($dom);
        $encoding = \strtoupper($dom->encoding);
        $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');

        $documents = [];

        foreach ($xml->xpath('//xliff:file') as $file) {
            $fileAttributes = $file->attributes();

            if (!isset($fileAttributes['original'])) {
                continue;
            }

            $uuid = (string) $fileAttributes['original'];
            $data = $this->getData($file, $encoding);

            $template = null;

            if (isset($data['structureType'])) {
                $template = $data['structureType']['value'];
                unset($data['structureType']);
            }

            $documents[] = [
                'uuid' => $uuid,
                'locale' => $locale,
                'structureType' => $template,
                'data' => $data,
            ];
        }

        return $documents;
    }

    /**
     * @param \SimpleXMLElement $file
     *
     * @return array
     */
    protected function getData($file, $encoding)
    {
        $data = [];

        foreach ($file->body->children() as $translation) {
            if (!$translation instanceof \SimpleXMLElement) {
                continue;
            }

            $attributes = $translation->attributes();
            if (!isset($attributes['resname'])) {
                continue;
            }

            $name = (string) $attributes['resname'];
            $value = $this->utf8ToCharset((string) $translation->target, $encoding);

            if (false === \strpos($name, '#')) {
                $property = [
                    'name' => $name,
                    'value' => $value,
                ];

                $data[$name] = $property;

                continue;
            }

            $blockData = $this->getBlockData($name, $value, $data);
            $data[$blockData['name']] = $blockData;
        }

        return $data;
    }

    private function getBlockData(string $name, string $value, array $data): array
    {
        $names = \explode('#', $name, 2);
        $blockName = $names[0];
        $names = \explode('-', $names[1], 2);
        $blockNr = $names[0];
        $name = $names[1];

        $blockData = $data[$blockName] ?? [
            'name' => $blockName,
            'children' => [
                $blockNr => [],
            ],
        ];

        if (false === \strpos($name, '#')) {
            $blockData['children'][$blockNr][$name] = [
                'name' => $name,
                'value' => $value,
            ];

            return $blockData;
        }

        $innerBlockData = $this->getBlockData($name, $value, $blockData['children'][$blockNr] ?? []);
        $blockData['children'][$blockNr][$innerBlockData['name']] = $innerBlockData;

        return $blockData;
    }

    /**
     * Part of Symfony XliffFileLoader.
     *
     * @param string $content String to decode
     * @param string $encoding Target encoding
     *
     * @return string
     */
    private function utf8ToCharset($content, $encoding = null)
    {
        if ('UTF-8' !== $encoding && !empty($encoding)) {
            if (\function_exists('mb_convert_encoding')) {
                return \mb_convert_encoding($content, $encoding, 'UTF-8');
            }

            if (\function_exists('iconv')) {
                return \iconv('UTF-8', $encoding, $content);
            }

            throw new \RuntimeException('No suitable convert encoding function (use UTF-8 as your encoding or install the iconv or mbstring extension).');
        }

        return $content;
    }
}
