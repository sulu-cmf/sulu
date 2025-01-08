<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageConverter;

use Imagine\Exception\RuntimeException;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidFormatOptionsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidImageFormat;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidFileTypeException;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Cropper\CropperInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Focus\FocusInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Scaler\ScalerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

/**
 * Sulu imagine converter for media.
 */
class ImagineImageConverter implements ImageConverterInterface
{
    public function __construct(
        private ImagineInterface $imagine,
        private StorageInterface $storage,
        private MediaImageExtractorInterface $mediaImageExtractor,
        private TransformationPoolInterface $transformationPool,
        private FocusInterface $focus,
        private ScalerInterface $scaler,
        private CropperInterface $cropper,
        private array $formats,
        private array $supportedMimeTypes,
        private ?ImagineInterface $svgImagine = null,
    ) {
    }

    public function getSupportedOutputImageFormats(?string $mimeType): array
    {
        if (!$mimeType) {
            return [];
        }

        foreach ($this->supportedMimeTypes as $supportedMimeType) {
            if (\fnmatch($supportedMimeType, $mimeType)) {
                $preferredExtension = 'jpg';

                switch ($mimeType) {
                    case 'image/png':
                        $preferredExtension = 'png';
                        break;
                    case 'image/svg+xml':
                    case 'image/svg':
                        $preferredExtension = 'png';
                        if ($this->svgImagine) {
                            $preferredExtension = 'svg';
                        }
                        break;
                    case 'image/webp':
                        $preferredExtension = 'webp';
                        break;
                    case 'image/gif':
                        $preferredExtension = 'gif';
                        break;
                    case 'image/avif':
                        $preferredExtension = 'avif';
                        break;
                }

                return \array_unique([
                    $preferredExtension,
                    'jpg',
                    'gif',
                    'png',
                    'webp',
                    'avif',
                ]);
            }
        }

        return [];
    }

    public function convert(FileVersion $fileVersion, $formatKey, $imageFormat)
    {
        $imageResource = $this->mediaImageExtractor->extract(
            $this->storage->load($fileVersion->getStorageOptions()),
            $fileVersion->getMimeType()
        );

        $imagine = $this->imagine;
        if ('svg' === $imageFormat && $this->svgImagine) {
            $imagine = $this->svgImagine;
        }

        try {
            $image = $imagine->read($imageResource);
        } catch (RuntimeException $e) {
            throw new InvalidFileTypeException($e->getMessage(), $e);
        }

        $image = $this->toRGB($image);

        $image = $this->autorotate($image);

        $format = $this->getFormat($formatKey);

        $cropParameters = $this->getCropParameters(
            $image,
            $fileVersion->getFormatOptions()->get($formatKey),
            $this->formats[$formatKey]
        );

        if (isset($cropParameters)) {
            $image = $this->applyFormatCrop($image, $cropParameters);
        } elseif (isset($format['scale']) && ImageInterface::THUMBNAIL_INSET !== $format['scale']['mode']) {
            $image = $this->applyFocus($image, $fileVersion, $format['scale']);
        }

        if (isset($format['scale'])) {
            $image = $this->applyScale($image, $format['scale']);
        }

        if (isset($format['transformations'])) {
            $image = $this->applyTransformations($image, $format['transformations']);
        }

        $image->strip();

        try {
            // Set Interlacing to plane for smaller image size.
            if (1 == \count($image->layers())) {
                $image->interlace(ImageInterface::INTERLACE_PLANE);
            }
        } catch (RuntimeException $exception) {
            // ignore exceptions here (some imagine adapter does not implement this)
        }

        $imagineOptions = $format['options'];

        return $image->get(
            $imageFormat,
            $this->getOptionsFromImage($image, $imageFormat, $imagineOptions)
        );
    }

    /**
     * Applies an array of transformations on a passed image.
     *
     * @param array $tansformations
     *
     * @return ImageInterface The modified image
     *
     * @throws ImageProxyInvalidFormatOptionsException
     */
    private function applyTransformations(ImageInterface $image, $tansformations)
    {
        foreach ($tansformations as $transformation) {
            if (!isset($transformation['effect'])) {
                throw new ImageProxyInvalidFormatOptionsException('Effect not found');
            }
            $image = $this->modifyAllLayers(
                $image,
                function(ImageInterface $layer) use ($transformation) {
                    return $this->transformationPool->get($transformation['effect'])->execute(
                        $layer,
                        $transformation['parameters']
                    );
                }
            );
        }

        return $image;
    }

    /**
     * Crops a given image according to given parameters.
     *
     * @param ImageInterface $image The image to crop
     * @param array $cropParameters The parameters which define the area to crop
     *
     * @return ImageInterface The cropped image
     */
    private function applyFormatCrop(ImageInterface $image, array $cropParameters)
    {
        return $this->modifyAllLayers(
            $image,
            function(ImageInterface $layer) use ($cropParameters) {
                return $this->cropper->crop(
                    $layer,
                    $cropParameters['x'],
                    $cropParameters['y'],
                    $cropParameters['width'],
                    $cropParameters['height']
                );
            }
        );
    }

    /**
     * Crops the given image according to the focus point defined in the file version.
     *
     * @return ImageInterface
     */
    private function applyFocus(ImageInterface $image, FileVersion $fileVersion, array $scale)
    {
        return $this->modifyAllLayers(
            $image,
            function(ImageInterface $layer) use ($fileVersion, $scale) {
                $focusX = $fileVersion->getFocusPointX();
                $focusY = $fileVersion->getFocusPointY();
                if (null === $focusX || null === $focusY) {
                    return $layer;
                }

                return $this->focus->focus(
                    $layer,
                    $focusX,
                    $focusY,
                    $scale['x'],
                    $scale['y']
                );
            }
        );
    }

    /**
     * Scales a given image according to the information passed as the second argument.
     *
     * @param array $scale
     *
     * @return ImageInterface
     */
    private function applyScale(ImageInterface $image, $scale)
    {
        return $this->modifyAllLayers(
            $image,
            function(ImageInterface $layer) use ($scale) {
                return $this->scaler->scale(
                    $layer,
                    $scale['x'],
                    $scale['y'],
                    $scale['mode'],
                    $scale['forceRatio'],
                    $scale['retina']
                );
            }
        );
    }

    /**
     * Ensures that the color mode of the passed image is RGB.
     *
     * @return ImageInterface $image The modified image
     */
    private function toRGB(ImageInterface $image)
    {
        if ('cmyk' == $image->palette()->name()) {
            $image->usePalette(new RGB());
        }

        return $image;
    }

    /**
     * Autorotate based on metadata of an image.
     *
     * @return ImageInterface
     */
    private function autorotate(ImageInterface $image)
    {
        $autorotateFilter = new Autorotate();

        return $autorotateFilter->apply($image);
    }

    /**
     * Constructs the parameters for the cropper. Returns null when
     * the image should not be cropped.
     *
     * @param FormatOptions|null $formatOptions
     *
     * @return ?array
     */
    private function getCropParameters(ImageInterface $image, $formatOptions, array $format)
    {
        if (isset($formatOptions)) {
            $parameters = [
                'x' => $formatOptions->getCropX(),
                'y' => $formatOptions->getCropY(),
                'width' => $formatOptions->getCropWidth(),
                'height' => $formatOptions->getCropHeight(),
            ];

            if ($this->cropper->isValid(
                $image,
                $parameters['x'],
                $parameters['y'],
                $parameters['width'],
                $parameters['height'],
                $format
            )) {
                return $parameters;
            }
        }

        return null;
    }

    /**
     * Applies a callback to every layer of an image and returns the resulting image.
     *
     * @param callable $modifier The callable to apply to all layers
     *
     * @return ImageInterface
     */
    private function modifyAllLayers(ImageInterface $image, callable $modifier)
    {
        try {
            $layers = $image->layers();
        } catch (RuntimeException $exception) {
            $layers = [];
        }

        if (\count($layers) > 1) {
            $countLayer = 0;
            $image->layers()->coalesce();

            /** @var ImageInterface $temporaryImage */
            $temporaryImage = null;
            foreach ($image->layers() as $layer) {
                ++$countLayer;
                $layer = \call_user_func($modifier, $layer);
                if (1 === $countLayer) {
                    $temporaryImage = clone $layer; // use first layer as main image
                } else {
                    $temporaryImage->layers()->add($layer);
                }
            }
            $image = $temporaryImage;
        } else {
            $image = \call_user_func($modifier, $image);
        }

        return $image;
    }

    /**
     * Return the options for the given format.
     *
     * @param string $formatKey
     *
     * @return array
     *
     * @throws ImageProxyInvalidImageFormat
     */
    private function getFormat($formatKey)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new ImageProxyInvalidImageFormat('Format was not found');
        }

        return $this->formats[$formatKey];
    }

    /**
     * @param string $imageExtension
     * @param array $imagineOptions
     *
     * @return array
     */
    private function getOptionsFromImage(ImageInterface $image, $imageExtension, $imagineOptions)
    {
        $options = [];
        // TODO avif support requires first support in imagemagick and then in imagine php library
        //     https://github.com/ImageMagick/ImageMagick/issues/6380
        //     https://github.com/php-imagine/Imagine/pull/812 (same changes for avif required)
        if (\in_array($imageExtension, ['gif', 'webp']) && \count($image->layers()) > 1) {
            $options['animated'] = true;
            $options['optimize'] = true;
        }

        return \array_merge($options, $imagineOptions);
    }
}
