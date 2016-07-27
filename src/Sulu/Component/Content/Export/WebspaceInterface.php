<?php
/*
 * This file is part of the Sulu CMS.
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
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );

    /**
     * @param string $webspaceKey
     * @param string $locale
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
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    );
}
