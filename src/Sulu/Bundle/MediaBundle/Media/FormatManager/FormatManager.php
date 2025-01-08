<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatManager;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidImageFormat;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\InvalidMimeTypeForPreviewException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\ImageConverterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sulu format manager for media.
 */
class FormatManager implements FormatManagerInterface
{
    /**
     * @var bool
     */
    private $saveImage = false;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ParameterBag|null
     */
    private $supportedImageFormats;

    /**
     * @param string $saveImage
     * @param array $responseHeaders
     * @param array $formats
     */
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private FormatCacheInterface $formatCache,
        private ImageConverterInterface $converter,
        $saveImage,
        private $responseHeaders,
        private $formats,
        ?LoggerInterface $logger = null
    ) {
        $this->saveImage = 'true' == $saveImage ? true : false;
        $this->fileSystem = new Filesystem();
        $this->logger = $logger ?: new NullLogger();
    }

    public function returnImage($id, $formatKey, $fileName /*, int<1, max>|null $version = null */)
    {
        /** @var int|null $version */
        $version = \func_num_args() > 3 ? \func_get_arg(3) : null;

        if (null === $version) {
            @trigger_deprecation('sulu/sulu', '2.5', 'The $version parameter in ' . __CLASS__ . '::' . __METHOD__ . ' is required.');
        }

        $setExpireHeaders = false;

        try {
            $info = \pathinfo($fileName);

            if (!isset($info['extension'])) {
                throw new ImageProxyInvalidUrl('No `extension` was found in the url');
            }

            $imageFormat = $info['extension'];

            $media = $this->mediaRepository->findMediaByIdForRendering($id, $formatKey, $version);

            if (!$media) {
                throw new ImageProxyMediaNotFoundException('Media was not found');
            }

            $fileVersion = $this->getLatestFileVersion($media);
            $version = null !== $version ? $version : $fileVersion->getVersion(); // TODO remove this line in Sulu 3.0 currently bc layer when version is not given

            /** @var File|null $file */
            $file = $media->getFiles()[0] ?? null;
            $requestedFileVersion = $file?->getFileVersion($version);

            if (!$requestedFileVersion) {
                throw new ImageProxyMediaNotFoundException('Requested FileVersion for media was not found');
            }

            $requestedFileVersionImageFormatName = $this->replaceExtension($requestedFileVersion->getName(), $imageFormat);

            if ($requestedFileVersionImageFormatName !== $fileName) {
                throw new ImageProxyMediaNotFoundException('File version was not found');
            }

            if ($fileVersion->getVersion() !== $requestedFileVersion->getVersion()) {
                $formats = $this->getFormats($id, $fileVersion->getName(), $fileVersion->getVersion(), $fileVersion->getSubVersion(), $fileVersion->getMimeType());

                $formatUrl = $formats[$formatKey . '.' . $imageFormat] ?? null;
                if (null === $formatUrl) {
                    throw new ImageProxyMediaNotFoundException('Image format "' . $formatKey . '.' . $imageFormat . '" was not found');
                }

                return new RedirectResponse($formatUrl, 301);
            }

            $supportedImageFormats = $this->converter->getSupportedOutputImageFormats($fileVersion->getMimeType());
            if (empty($supportedImageFormats)) {
                throw new InvalidMimeTypeForPreviewException($fileVersion->getMimeType() ?? '-null-');
            }

            if (!\in_array($imageFormat, $supportedImageFormats)) {
                throw new ImageProxyInvalidImageFormat(
                    \sprintf(
                        'Image format "%s" not supported. Supported image formats are: %s',
                        $imageFormat,
                        \implode(', ', $supportedImageFormats)
                    ));
            }

            // Convert Media to format.
            $responseContent = $this->converter->convert($fileVersion, $formatKey, $imageFormat);

            // HTTP Headers
            $status = 200;
            $setExpireHeaders = true;

            $finfo = new \finfo(\FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($responseContent);

            // Save image.
            if ($this->saveImage) {
                $this->formatCache->save(
                    $responseContent,
                    $media->getId(),
                    $this->replaceExtension($fileVersion->getName(), $imageFormat),
                    $formatKey
                );
            }
        } catch (MediaException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $responseContent = null;
            $status = 404;
            $mimeType = null;
        }

        // Set header.
        $headers = $this->getResponseHeaders($mimeType, $setExpireHeaders);

        // Return image.
        return new Response($responseContent, $status, $headers);
    }

    public function getFormats($id, $fileName, $version, $subVersion, $mimeType)
    {
        $formats = [];

        $extensions = $this->converter->getSupportedOutputImageFormats($mimeType);

        if (empty($extensions)) {
            return [];
        }

        $originalExtension = $extensions[0];
        foreach ($this->formats as $format) {
            foreach ($extensions as $extension) {
                $formatUrl = $this->formatCache->getMediaUrl(
                    $id,
                    $this->replaceExtension($fileName, $extension),
                    $format['key'],
                    $version,
                    $subVersion
                );

                if ($extension === $originalExtension) {
                    $formats[$format['key']] = $formatUrl;
                }

                $formats[$format['key'] . '.' . $extension] = $formatUrl;
            }
        }

        return $formats;
    }

    public function purge($idMedia, $fileName, $mimeType)
    {
        $extensions = $this->converter->getSupportedOutputImageFormats($mimeType);

        if (empty($extensions)) {
            return true;
        }

        foreach ($this->formats as $format) {
            foreach ($extensions as $extension) {
                $this->formatCache->purge($idMedia, $this->replaceExtension($fileName, $extension), $format['key']);
            }
        }

        return true;
    }

    public function clearCache()
    {
        $this->formatCache->clear();
    }

    public function getFormatDefinition($formatKey, $locale = null)
    {
        if (!isset($this->formats[$formatKey])) {
            throw new FormatNotFoundException($formatKey);
        }

        $format = $this->formats[$formatKey];
        $title = $format['key'];

        if (\array_key_exists($locale, $format['meta']['title'])) {
            $title = $format['meta']['title'][$locale];
        } elseif (\count($format['meta']['title']) > 0) {
            $title = \array_values($format['meta']['title'])[0];
        }

        $formatArray = [
            'internal' => $format['internal'],
            'key' => $format['key'],
            'title' => $title,
            'scale' => $format['scale'],
        ];

        return $formatArray;
    }

    public function getFormatDefinitions($locale = null)
    {
        $definitionsArray = [];

        foreach ($this->formats as $format) {
            $definitionsArray[$format['key']] = $this->getFormatDefinition($format['key'], $locale);
        }

        return $definitionsArray;
    }

    /**
     * @param string $mimeType
     * @param bool $setExpireHeaders
     *
     * @return array
     */
    protected function getResponseHeaders($mimeType = '', $setExpireHeaders = false)
    {
        $headers = [];

        if (!empty($mimeType)) {
            $headers['Content-Type'] = $mimeType;
        }

        if (empty($this->responseHeaders)) {
            return $headers;
        }

        $headers = \array_merge(
            $headers,
            $this->responseHeaders
        );

        if (isset($this->responseHeaders['Expires']) && $setExpireHeaders) {
            $date = new \DateTime();
            $date->modify($this->responseHeaders['Expires']);
            $headers['Expires'] = $date->format('D, d M Y H:i:s \G\M\T');
        } elseif (!$setExpireHeaders) {
            // will remove exist set expire header
            $headers['Expires'] = null;
            $headers['Cache-Control'] = 'no-cache';
            $headers['Pragma'] = null;
        }

        return $headers;
    }

    /**
     * Replace extension.
     *
     * @param string $filename
     * @param string $newExtension
     *
     * @return string
     */
    private function replaceExtension($filename, $newExtension)
    {
        $info = \pathinfo($filename);

        return $info['filename'] . '.' . $newExtension;
    }

    /**
     * @return FileVersion
     *
     * @throws ImageProxyMediaNotFoundException
     */
    private function getLatestFileVersion(MediaInterface $media)
    {
        foreach ($media->getFiles() as $file) {
            $version = $file->getVersion();
            foreach ($file->getFileVersions() as $fileVersion) {
                if ($fileVersion->getVersion() == $version) {
                    return $fileVersion;
                }
            }
            break;
        }

        throw new ImageProxyMediaNotFoundException('Media file version was not found');
    }
}
