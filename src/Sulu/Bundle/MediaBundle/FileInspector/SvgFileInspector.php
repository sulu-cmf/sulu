<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\FileInspector;

use HtmlSanitizer\SanitizerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @final
 */
class SvgFileInspector implements FileInspectorInterface
{
    private const UNSAFE_ELEMENTS = ['script', 'iframe', 'foreignObject', 'use', 'image', 'animate'];
    private const UNSAFE_ATTRIBUTES = ['on', 'xlink:href', 'href'];

    public function __construct(
        private SanitizerInterface $htmlSanitizer,
        private SanitizerInterface $htmlSanitizerSafe,
    ) {
    }

    public function supports(string $mimeType): bool
    {
        return 'image/svg+xml' === $mimeType;
    }

    public function inspect(UploadedFile $file): UploadedFile
    {
        $svg = $file->getContent();

        if ($this->containsUnsafeContent($svg)) {
            throw new UnsafeFileException($file);
        }

        $cleanedUpSvg = $this->htmlSanitizer->sanitize($svg);
        $safeSvg = $this->htmlSanitizerSafe->sanitize($svg);

        if ($this->normalizeString($cleanedUpSvg) !== $this->normalizeString($safeSvg)) {
            throw new UnsafeFileException($file);
        }

        return $file;
    }

    private function containsUnsafeContent(string $svg): bool
    {
        \libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($svg, \LIBXML_NOENT | \LIBXML_DTDLOAD);
        \libxml_clear_errors();

        foreach (self::UNSAFE_ELEMENTS as $element) {
            if ($dom->getElementsByTagName($element)->length > 0) {
                return true;
            }
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        foreach (self::UNSAFE_ATTRIBUTES as $attr) {
            $query = "//*[starts-with(name(@*), '$attr') or @*[contains(., 'javascript:')]]";
            $node = $xpath->query($query);
            if (false === $node || $node->length > 0) {
                return true;
            }
        }

        // Check for event handlers
        $eventHandlerQuery = "//*[@*[starts-with(name(), 'on')]]";
        $node = $xpath->query($eventHandlerQuery);
        if (false === $node || $node->length > 0) {
            return true;
        }

        // Check for data URIs
        if (\str_contains($svg, 'data:')) {
            return true;
        }

        return false;
    }

    private function normalizeString(string $input): string
    {
        return \strtolower((string) \preg_replace('/\s+/', '', $input));
    }
}
