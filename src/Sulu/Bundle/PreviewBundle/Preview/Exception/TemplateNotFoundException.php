<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Exception;

/**
 * This exception will be thrown when the twig-template was not found.
 */
class TemplateNotFoundException extends PreviewRendererException
{
    /**
     * @param int $object
     * @param string $webspaceKey
     * @param string $locale
     */
    public function __construct(\InvalidArgumentException $exception, $object, $id, $webspaceKey, $locale)
    {
        parent::__construct(
            $exception->getMessage(),
            self::BASE_CODE + 4,
            $object,
            $id,
            $webspaceKey,
            $locale,
            $exception
        );
    }
}
