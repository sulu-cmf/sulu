<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

use Sulu\Bundle\MediaBundle\Entity\MediaType;

/**
 * Interface TypeManagerInterface
 * The Type Manager returns the media types or return a media type for a specific mime type.
 */
interface TypeManagerInterface
{
    public const ENTITY_CLASS_MEDIATYPE = MediaType::class;

    public const ENTITY_NAME_MEDIATYPE = MediaType::class;

    /**
     * Returns a Media Type by a given ID.
     *
     * @param int $id
     *
     * @return MediaType
     */
    public function get($id);

    /**
     * Returns a Media Type ID by a given mime type.
     *
     * @param string $fileMimeType
     *
     * @return int
     */
    public function getMediaType($fileMimeType);
}
