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

class UnsafeFileException extends \Exception
{
    public function __construct(
        private UploadedFile $uploadedFile,
    ) {
        parent::__construct(\sprintf(
            'The file "%s" is not safe.',
            $uploadedFile->getClientOriginalName(),
        ));
    }

    public function getUploadedFile(): UploadedFile
    {
        return $this->uploadedFile;
    }
}
