<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\EventSubscriber;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds tags to the Symfony response tagger from all available reference stores.
 */
class TagsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ReferenceStorePoolInterface $referenceStorePool,
        private SymfonyResponseTagger $symfonyResponseTagger,
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['addTags', 1024],
        ];
    }

    /**
     * Adds tags from the reference store to the response tagger.
     */
    public function addTags(): void
    {
        $tags = $this->getTags();
        $currentStructureUuid = $this->getCurrentStructureUuid();
        if ($currentStructureUuid && !\in_array($currentStructureUuid, $tags)) {
            $tags[] = $currentStructureUuid;
        }

        if (\count($tags) <= 0) {
            return;
        }

        $this->symfonyResponseTagger->addTags($tags);
    }

    /**
     * Merges tags from all registered stores.
     */
    private function getTags(): array
    {
        $tags = [];
        foreach ($this->referenceStorePool->getStores() as $alias => $referenceStore) {
            $tags = \array_merge($tags, $this->getTagsFromStore($alias, $referenceStore));
        }

        return $tags;
    }

    /**
     * Returns tags from given store.
     */
    private function getTagsFromStore($alias, ReferenceStoreInterface $referenceStore): array
    {
        $tags = [];
        foreach ($referenceStore->getAll() as $reference) {
            $tag = $reference;
            if (!Uuid::isValid($reference)) {
                $tag = $alias . '-' . $reference;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Returns uuid of current structure.
     */
    private function getCurrentStructureUuid(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $structure = $request->get('structure');
        if (!$structure || !$structure instanceof StructureInterface) {
            return null;
        }

        return $structure->getUuid();
    }
}
