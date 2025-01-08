<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

use PHPCR\NodeInterface;

interface ExportExtensionInterface
{
    /**
     * @param array<string, mixed> $properties
     * @param string $format
     *
     * @return array<string, array{name: string, value: mixed, type: string, options: array<string, mixed>}>
     */
    public function export($properties, $format = null);

    /**
     * @return string[]
     */
    public function getImportPropertyNames();

    /**
     * @param array $data
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $format
     */
    public function import(NodeInterface $node, $data, $webspaceKey, $languageCode, $format);
}
