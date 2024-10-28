<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\FileInspector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @internal
 */
final class UploadFileSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }

    /**
     * @var FileInspectorInterface[]
     */
    private array $fileInspectors;

    /**
     * @param \Traversable<FileInspectorInterface> $fileInspectors
     */
    public function __construct(
        iterable $fileInspectors,
    ) {
        $this->fileInspectors = \iterator_to_array($fileInspectors);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $request->files->replace($this->inspectFiles($request->files->all()));
    }

    /**
     * @param array<string, mixed> $files
     *
     * @return array<string, mixed>
     */
    private function inspectFiles(array $files): array
    {
        /**
         * @var string $key
         * @var UploadedFile|array<string, mixed>|null $file
         */
        foreach ($files as $key => $file) {
            if (\is_array($file)) {
                $files[$key] = $this->inspectFiles($file);
                continue;
            }

            if (null === $file) {
                continue;
            }

            $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
            foreach ($this->fileInspectors as $fileInspector) {
                if (null !== $mimeType && $fileInspector->supports($mimeType)) {
                    try {
                        $files[$key] = $fileInspector->inspect($file);
                    } catch (UnsafeFileException $exception) {
                        throw new BadRequestHttpException($exception->getMessage(), $exception);
                    }
                }
            }
        }

        return $files;
    }
}
