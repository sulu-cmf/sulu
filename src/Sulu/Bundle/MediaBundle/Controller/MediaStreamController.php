<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\DispositionType\DispositionTypeResolver;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaException;
use Sulu\Bundle\MediaBundle\Media\FormatCache\FormatCacheInterface;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MediaStreamController
{
    public function __construct(
        protected DispositionTypeResolver $dispositionTypeResolver,
        protected MediaRepositoryInterface $mediaRepository,
        protected PathCleanupInterface $pathCleaner,
        protected FormatManagerInterface $formatManager,
        protected FormatCacheInterface $formatCache,
        protected MediaManagerInterface $mediaManager,
        protected StorageInterface $storage,
        protected ?SecurityCheckerInterface $securityChecker = null
    ) {
    }

    /**
     * @return Response
     */
    public function getImageAction(Request $request)
    {
        try {
            if (\ob_get_length()) {
                \ob_end_clean();
            }

            $url = $request->getPathInfo();
            // Some projects do not call this action with ?v=1-0 because they don't want query strings in the image urls ( unnecessary SEO mystic reasons )
            // To maintain compatibility with these projects, we will fallback to version 1-0 if no version is specified.
            $version = (string) $request->query->get('v', '1-0');
            $version = (int) (\explode('-', $version)[0] ?? '1');

            $mediaProperties = $this->formatCache->analyzedMediaUrl($url);

            return $this->formatManager->returnImage(
                $mediaProperties['id'],
                $mediaProperties['format'],
                $mediaProperties['fileName'],
                $version,
            );
        } catch (ImageProxyException $e) {
            throw new NotFoundHttpException('Image create error. Code: ' . $e->getCode(), $e);
        }
    }

    /**
     * @param int $id
     * @param string $slug
     *
     * @return Response
     */
    public function downloadAction(Request $request, $id, $slug)
    {
        try {
            if (\ob_get_length()) {
                \ob_end_clean();
            }

            $version = $request->get('v', null);
            $version = \is_numeric($version) ? ((int) $version) : null;
            $noCount = $request->get('no-count', false);

            $fileVersion = $this->getFileVersion($id, $version);

            if (!$fileVersion) {
                return new Response('Invalid version "' . $version . '" for media with ID "' . $id . '".', 404);
            }

            if ($fileVersion->getName() !== $slug) {
                return new Response('Invalid file name for media with ID "' . $id . '".', 404);
            }

            if ($this->securityChecker) {
                $this->securityChecker->checkPermission(
                    new SecurityCondition(
                        MediaAdmin::SECURITY_CONTEXT,
                        null,
                        Collection::class,
                        $fileVersion->getFile()->getMedia()->getCollection()->getId()
                    ),
                    PermissionTypes::VIEW
                );
            }

            if ($request->query->has('inline')) {
                $forceInline = (bool) $request->get('inline', false);
                $dispositionType = $forceInline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT;
            } else {
                $dispositionType = $this->dispositionTypeResolver->getByMimeType($fileVersion->getMimeType());
            }

            if (!$noCount) {
                $this->mediaManager->increaseDownloadCounter($fileVersion->getId());
            }

            $response = $this->getFileResponse($fileVersion, $request->getLocale(), $dispositionType);

            return $response;
        } catch (MediaException $e) {
            throw new NotFoundHttpException('File not found: ' . $e->getCode() . ' ' . $e->getMessage(), $e);
        }
    }

    protected function getFileResponse(
        FileVersion $fileVersion,
        string $locale,
        string $dispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT
    ): Response {
        $storageOptions = $fileVersion->getStorageOptions();

        $storageType = $this->storage->getType($storageOptions);

        if (StorageInterface::TYPE_REMOTE === $storageType) {
            $response = new RedirectResponse($this->storage->getPath($storageOptions), 302);
            $response->setPrivate();

            return $response;
        } elseif (StorageInterface::TYPE_LOCAL === $storageType) {
            return $this->createBinaryFileResponse($fileVersion, $this->storage, $locale, $dispositionType);
        }

        throw new \RuntimeException(\sprintf('Storage type "%s" not supported.', $storageType));
    }

    private function createBinaryFileResponse(
        FileVersion $fileVersion,
        StorageInterface $storage,
        string $locale,
        string $dispositionType
    ): BinaryFileResponse {
        $fileName = $fileVersion->getName();
        $fileSize = $fileVersion->getSize();
        $storageOptions = $fileVersion->getStorageOptions();
        $mimeType = $fileVersion->getMimeType();
        $lastModified = $fileVersion->getCreated(); // use created as file itself is not changed when entity is changed

        $response = new BinaryFileResponse($storage->getPath($storageOptions));

        // Prepare headers
        $disposition = $response->headers->makeDisposition(
            $dispositionType,
            $fileName,
            $this->cleanUpFileName($fileName, $locale, $fileVersion->getExtension())
        );

        // Set headers for
        $file = $fileVersion->getFile();
        if ($fileVersion->getVersion() !== $file->getVersion()) {
            $latestFileVersion = $file->getLatestFileVersion();

            $response->headers->set(
                'Link',
                \sprintf(
                    '<%s>; rel="canonical"',
                    $this->mediaManager->getUrl(
                        $file->getMedia()->getId(),
                        $latestFileVersion->getName(),
                        $latestFileVersion->getVersion()
                    )
                )
            );
            $response->headers->set('X-Robots-Tag', 'noindex, follow');
        }

        // Set headers
        $response->headers->set('Content-Type', !empty($mimeType) ? $mimeType : 'application/octet-stream');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-length', $fileSize);
        $response->headers->set('Last-Modified', $lastModified->format('D, d M Y H:i:s \G\M\T'));

        return $response;
    }

    /**
     * @param int $id
     * @param int|null $version
     *
     * @return FileVersion|null
     *
     * @throws FileVersionNotFoundException
     */
    protected function getFileVersion($id, $version)
    {
        /** @var MediaInterface|null $mediaEntity */
        $mediaEntity = $this->mediaRepository->findMediaByIdForRendering($id, null, $version);

        if (!$mediaEntity) {
            return null;
        }

        $file = $mediaEntity->getFiles()[0] ?? null;

        if (!$file) {
            return null;
        }

        if (!$version) {
            $version = $file->getVersion();
        }

        $fileVersion = $file->getFileVersion($version);

        if (!$fileVersion) {
            throw new FileVersionNotFoundException($id, $version);
        }

        return $fileVersion;
    }

    /**
     * Cleaned up filename.
     *
     * @param string $fileName
     * @param string $locale
     * @param string $extension
     *
     * @return string
     */
    private function cleanUpFileName($fileName, $locale, $extension)
    {
        $pathInfo = \pathinfo($fileName);
        $cleanedFileName = $this->pathCleaner->cleanup($pathInfo['filename'], $locale);
        if ($extension) {
            $cleanedFileName .= '.' . $extension;
        }

        return $cleanedFileName;
    }
}
