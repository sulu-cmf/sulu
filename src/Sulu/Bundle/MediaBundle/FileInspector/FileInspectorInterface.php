<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\FileInspector;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileInspectorInterface
{
    public function supports(string $mimeType): bool;

    /**
     * @throws UnsafeFileException
     */
    public function inspect(UploadedFile $file): UploadedFile;
}
