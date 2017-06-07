<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\DispositionType;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Media disposition type resolver.
 */
class DispositionTypeResolver implements DispositionTypeResolverInterface
{
    /**
     * @var string
     */
    protected $defaultType;

    /**
     * @var array
     */
    protected $mimeTypesInline = [];

    /**
     * @var array
     */
    protected $mimeTypesAttachment = [];

    /**
     * DispositionTypeResolver constructor.
     *
     * @param string $defaultType
     * @param array $mimeTypesInline
     * @param array $mimeTypesAttachment
     */
    public function __construct($defaultType, array $mimeTypesInline = [], array $mimeTypesAttachment = [])
    {
        $this->defaultType = $defaultType;
        $this->mimeTypesInline = $mimeTypesInline;
        $this->mimeTypesAttachment = $mimeTypesAttachment;
    }

    /**
     * {@inheritdoc}
     */
    public function getByMimeType($mimeType)
    {
        if (in_array($mimeType, $this->mimeTypesInline)) {
            return ResponseHeaderBag::DISPOSITION_INLINE;
        } elseif (in_array($mimeType, $this->mimeTypesAttachment)) {
            return ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        }

        return $this->defaultType;
    }
}
