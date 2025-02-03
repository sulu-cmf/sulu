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
use Massive\Bundle\SearchBundle\Search\SearchEvents;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber populates the image URL field
 * when a Structure containing an image field is indexed.
 */
class StructureMediaSearchSubscriber implements EventSubscriberInterface
{
    /**
     * @param string $searchImageFormat the format of the image which will be returned in the search
     */
    public function __construct(
        protected MediaManagerInterface $mediaManager,
        protected ?RequestAnalyzerInterface $requestAnalyzer,
        protected $searchImageFormat
    ) {
    }

    /**
     * Returns the events this subscriber has subscribed.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SearchEvents::PRE_INDEX => 'handlePreIndex',
        ];
    }

    /**
     * Adds the image to the search document.
     */
    public function handlePreIndex(PreIndexEvent $e)
    {
        $metadata = $e->getMetadata();
        $document = $e->getDocument();
        $subject = $e->getSubject();
        $evaluator = $e->getFieldEvaluator();

        if (
            !$e->getSubject() instanceof StructureBehavior
            && StructureBehavior::class !== $metadata->getName()
        ) {
            return;
        }

        if (!$imageUrlField = $metadata->getImageUrlField()) {
            return;
        }

        $data = $evaluator->getValue($subject, $imageUrlField);
        $locale = $subject->getLocale();

        if (!$data) {
            $document->setImageUrl(null);

            return;
        }

        $imageUrl = $this->getImageUrl($data, $locale);
        $document->setImageUrl($imageUrl);
    }

    /**
     * Returns the url for the image.
     *
     * @param array|MediaSelectionContainer $data
     * @param string $locale
     *
     * @return string|null
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function getImageUrl($data, $locale)
    {
        // new structures will container an instance of MediaSelectionContainer
        if ($data instanceof MediaSelectionContainer) {
            $medias = $data->getData();
        } else {
            $ids = [];

            if (isset($data['ids'])) {
                $ids = \array_merge($ids, $data['ids']);
            } elseif (isset($data['id'])) {
                $ids = \array_merge($ids, [$data['id']]);
            }

            if (0 === \count($ids)) {
                return null;
            }

            $medias = $this->mediaManager->get($locale, [
                'ids' => $ids,
            ]);
        }

        // no media, no thumbnail URL
        if (!$medias) {
            return null;
        }

        $media = \current($medias);

        if (!$media) {
            return null;
        }

        $formats = $media->getThumbnails();

        if (empty($formats)) {
            return null;
        }

        if (!isset($formats[$this->searchImageFormat])) {
            throw new \InvalidArgumentException(
                \sprintf('Search image format "%s" is not known', $this->searchImageFormat)
            );
        }

        return $formats[$this->searchImageFormat];
    }
}
