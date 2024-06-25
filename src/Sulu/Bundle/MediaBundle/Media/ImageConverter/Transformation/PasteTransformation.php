<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Config\FileLocator;

/**
 * Class PasteTransformation.
 */
class PasteTransformation implements TransformationInterface
{
    public function __construct(
        private ImagineInterface $imagine,
        private FileLocator $fileLocator,
    ) {
    }

    public function execute(ImageInterface $image, $parameters)
    {
        $maskPath = isset($parameters['image']) ? $this->fileLocator->locate($parameters['image']) : null;

        if (!$maskPath) {
            throw new \RuntimeException('The parameter "image" is required for "paste" transformation.');
        }

        $originalWidth = $image->getSize()->getWidth();
        $originalHeight = $image->getSize()->getHeight();
        $top = isset($parameters['top']) ? $parameters['top'] : 0;
        $left = isset($parameters['left']) ? $parameters['left'] : 0;

        $width = isset($parameters['width']) ? $parameters['width'] : $originalWidth;
        $height = isset($parameters['height']) ? $parameters['height'] : $originalHeight;

        // imagine will error when mask is bigger then the given image
        // this could happen in forceRatio true mode so we need also scale the mask
        if ($width > $originalWidth) {
            $width = $originalWidth;
            $height = (int) ($height / $width * $originalWidth);
        }

        if ($height > $originalHeight) {
            $height = $originalHeight;
            $width = (int) ($width / $height * $originalHeight);
        }

        // create mask
        $mask = $this->createMask(
            $maskPath,
            $width,
            $height
        );

        // add mask to image
        $image->paste($mask, new Point($top, $left));

        return $image;
    }

    /**
     * Create mask.
     *
     * @param string|array $maskPath The full path to the file or an array of file paths
     * @param int $width
     * @param int $height
     *
     * @return ImageInterface
     */
    protected function createMask($maskPath, $width, $height)
    {
        $mask = $this->imagine->open($maskPath);
        $mask->resize(
            new Box(
                $width ?: 1,
                $height ?: 1
            )
        );

        return $mask;
    }
}
