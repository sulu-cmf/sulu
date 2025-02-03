<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Search\Subscriber;

use Massive\Bundle\SearchBundle\Search\Event\PreIndexEvent;
use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber populates the image URL field
 * when a Structure containing an image field is indexed.
 */
class MediaSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Factory $factory Massive search factory
     * @param array $thumbnailMimeTypes
     * @param string $searchImageFormat
     */
    public function __construct(
        protected MediaManagerInterface $mediaManager,
        protected Factory $factory,
        protected $thumbnailMimeTypes,
        /**
         * The format of the image, which will be returned in the search.
         */
        protected $searchImageFormat,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?: new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        ];
    }

    /**
     * Adds the image to the search document.
     */
    public function handlePreIndex(PreIndexEvent $event)
    {
        $metadata = $event->getMetadata();

        if (
            !$event->getSubject() instanceof FileVersionMeta
            && FileVersionMeta::class !== $metadata->getName()
        ) {
            return;
        }

        $document = $event->getDocument();
        $subject = $event->getSubject();
        $locale = $subject->getLocale();

        $fileVersion = $subject->getFileVersion();
        $file = $fileVersion->getFile();
        $media = $file->getMedia();

        // Do not try and get the image URL if the mime type is not in the
        // list of mime types for which thumbnails are generated.
        foreach ($this->thumbnailMimeTypes as $type) {
            if (\fnmatch($type, $fileVersion->getMimeType())) {
                $document->setImageUrl($this->getImageUrl($media, $locale));
                break;
            }
        }

        $document->addField($this->factory->createField(
            'media_id',
            $media->getId()
        ));

        $document->addField($this->factory->createField(
            'media_mime',
            $fileVersion->getMimeType()
        ));

        if ($collection = $media->getCollection()) {
            $document->addField($this->factory->createField(
                'collection_id',
                $collection->getId()
            ));
        }
    }

    /**
     * Return the image URL for the given media.
     *
     * TODO: The media API needs to be improved here.
     */
    private function getImageUrl($media, $locale)
    {
        $mediaApi = new Media($media, $locale);
        $this->mediaManager->addFormatsAndUrl($mediaApi);
        $formats = $mediaApi->getThumbnails();

        if (!isset($formats[$this->searchImageFormat])) {
            $this->logger->warning(\sprintf(
                'Media with ID "%s" does not have thumbnail format "%s". This thumbnail would be used by the search results.',
                $media->getId(),
                $this->searchImageFormat
            ));

            return;
        }

        return $formats[$this->searchImageFormat];
    }
}
