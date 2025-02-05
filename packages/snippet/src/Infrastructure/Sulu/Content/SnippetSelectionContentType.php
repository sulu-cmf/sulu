<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Infrastructure\Sulu\Content;

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

class SnippetSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    private ReferenceStoreInterface $referenceStore;
    private ContentManagerInterface $contentManager;
    private SnippetRepositoryInterface $snippetRepository;

    public function __construct(
        SnippetRepositoryInterface $snippetRepository,
        ContentManagerInterface $contentManager,
        ReferenceStoreInterface $referenceStore
    ) {
        parent::__construct('Snippet', []);
        $this->referenceStore = $referenceStore;
        $this->contentManager = $contentManager;
        $this->snippetRepository = $snippetRepository;
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if (!\is_array($value) || !\array_is_list($value)) {
            return [];
        }

        /** @var string[] $uuids */
        $uuids = [];
        foreach ($value as $uuid) {
            if (!\is_string($uuid)) {
                return [];
            }

            $uuids[] = $uuid;
        }

        if (0 === \count($value)) {
            return [];
        }

        $dimensionAttributes = [
            'locale' => $property->getStructure()->getLanguageCode(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ];

        $snippet = $this->snippetRepository->findBy(
            filters: \array_merge(
                ['uuids' => $uuids],
                $dimensionAttributes,
            ),
            selects: [
                SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_WEBSITE => true,
            ]);

        $result = [];
        foreach ($snippet as $snippet) {
            $dimensionContent = $this->contentManager->resolve($snippet, $dimensionAttributes);
            $result[\array_search($snippet->getUuid(), $uuids, false)] = $this->contentManager->normalize($dimensionContent);
        }

        \ksort($result);

        return \array_values($result);
    }

    public function preResolve(PropertyInterface $property): void
    {
        $uuids = $property->getValue();
        if (!\is_array($uuids)) {
            return;
        }

        foreach ($uuids as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }
}
