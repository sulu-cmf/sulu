<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Export\Manager;

/**
 * Interface for Content Export Manager.
 */
interface ExportManagerInterface
{
    /**
     * Add ContentType and the format.
     * This will be set on the Content-Type Service.
     *
     * @param string $contentTypeName
     * @param string $format
     * @param mixed[] $options
     */
    public function add($contentTypeName, $format, $options);

    /**
     * Export data for document by given Content-Type.
     *
     * @param string $contentTypeName
     */
    public function export($contentTypeName, $propertyValue);

    /**
     * Checks the content-type if this has an export.
     *
     * @param string $contentTypeName
     * @param string $format
     *
     * @return bool
     */
    public function hasExport($contentTypeName, $format);

    /**
     * Returns the export options by the given content-type.
     *
     * @param string $contentTypeName
     * @param string $format
     *
     * @return array<string, mixed>
     */
    public function getOptions($contentTypeName, $format);
}
