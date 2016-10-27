<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

/**
 * Interface for Webspace export.
 */
interface WebspaceInterface
{
    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param $output
     * @param string $format
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     */
    public function export(
        $webspaceKey,
        $locale,
        $output,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param $output
     * @param string $format
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     */
    public function getExportData(
        $webspaceKey,
        $locale,
        $output,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );
}
