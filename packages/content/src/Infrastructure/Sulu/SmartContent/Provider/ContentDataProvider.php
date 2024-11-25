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

namespace Sulu\Content\Infrastructure\Sulu\SmartContent\Provider;

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\SmartContent\ArrayAccessItem;
use Sulu\Component\SmartContent\Configuration\BuilderInterface;
use Sulu\Component\SmartContent\ItemInterface;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\ResourceItemInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\SmartContent\DataItem\ContentDataItem;

class ContentDataProvider extends BaseDataProvider
{
    /**
     * @var ContentManagerInterface
     */
    protected $contentManager;

    /**
     * @var ReferenceStoreInterface|null
     */
    protected $referenceStore;

    public function __construct(
        DataProviderRepositoryInterface $repository,
        ArraySerializerInterface $arraySerializer,
        ContentManagerInterface $contentManager,
        ?ReferenceStoreInterface $referenceStore = null
    ) {
        parent::__construct($repository, $arraySerializer);

        $this->contentManager = $contentManager;
        $this->referenceStore = $referenceStore;

        $configurationBuilder = static::createConfigurationBuilder();

        $this->configure($configurationBuilder);

        $this->configuration = $configurationBuilder->getConfiguration();
    }

    protected function configure(BuilderInterface $builder): void
    {
        $builder
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableSorting(
                [
                    ['column' => 'workflowPublished', 'title' => 'sulu_content.published'],
                ]
            );
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T[] $data
     *
     * @return mixed[]
     */
    protected function decorateDataItems(array $data): array
    {
        return \array_map(
            function(DimensionContentInterface $dimensionContent) {
                $normalizedContentData = $this->normalizeContent($dimensionContent);

                return $this->createDataItem($dimensionContent, $normalizedContentData);
            },
            $data
        );
    }

    /**
     * Decorates result as resource item.
     *
     * @template T of DimensionContentInterface
     *
     * @param T[] $data
     * @param string $locale
     *
     * @return ResourceItemInterface[]
     */
    protected function decorateResourceItems(array $data, $locale): array
    {
        return \array_map(
            function(DimensionContentInterface $dimensionContent) {
                $normalizedContentData = $this->normalizeContent($dimensionContent);
                $id = $this->getIdForItem($dimensionContent);

                if (null !== $this->referenceStore) {
                    $this->referenceStore->add($id);
                }

                return $this->createResourceItem($id, $dimensionContent, $normalizedContentData);
            },
            $data
        );
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return string|int
     */
    protected function getIdForItem($dimensionContent)
    {
        return $dimensionContent->getResource()->getId();
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     *
     * @return mixed[]
     */
    protected function normalizeContent(DimensionContentInterface $dimensionContent): array
    {
        return $this->contentManager->normalize($dimensionContent);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param T $dimensionContent
     * @param mixed[] $data
     */
    protected function createDataItem(DimensionContentInterface $dimensionContent, array $data): ItemInterface
    {
        return new ContentDataItem($dimensionContent, $data);
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param string|int $id
     * @param T $dimensionContent
     * @param mixed[] $data
     */
    protected function createResourceItem($id, DimensionContentInterface $dimensionContent, array $data): ResourceItemInterface
    {
        return new ArrayAccessItem($id, $data, $dimensionContent);
    }
}
