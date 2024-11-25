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

namespace Sulu\Content\Infrastructure\Sulu\Search;

use Doctrine\ORM\EntityManagerInterface;
use Massive\Bundle\SearchBundle\Search\Reindex\LocalizedReindexProviderInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
class ContentReindexProvider implements LocalizedReindexProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContentMetadataInspectorInterface
     */
    private $contentMetadataInspector;

    /**
     * @var ContentAggregatorInterface
     */
    private $contentAggregator;

    /**
     * @var string
     */
    private $context;

    /**
     * @var class-string<T>
     */
    private $contentRichEntityClass;

    /**
     * @var class-string<B>|null
     */
    private $dimensionContentClass;

    /**
     * @param class-string<T> $contentRichEntityClass
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        ContentAggregatorInterface $contentAggregator,
        string $context,
        string $contentRichEntityClass
    ) {
        $this->entityManager = $entityManager;
        $this->contentMetadataInspector = $contentMetadataInspector;
        $this->contentAggregator = $contentAggregator;
        $this->context = $context;
        $this->contentRichEntityClass = $contentRichEntityClass;
    }

    public function provide($classFqn, $offset, $maxResults)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($this->contentRichEntityClass, 'contentRichEntity')
            ->select('contentRichEntity')
            ->setFirstResult($offset)
            ->setMaxResults($maxResults);

        /** @var array<T> */
        return $queryBuilder->getQuery()->execute();
    }

    /**
     * TODO FIXME add test case for this.
     *
     * @codeCoverageIgnore
     */
    public function cleanUp($classFqn): void
    {
        $this->entityManager->clear(); // @codeCoverageIgnore
    }

    public function getCount($classFqn)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($this->contentRichEntityClass, 'contentRichEntity')
            ->select('COUNT(contentRichEntity)');

        /** @var int */
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function getClassFqns()
    {
        return [$this->getDimensionContentClass()];
    }

    public function getLocalesForObject($object)
    {
        if (!$object instanceof ContentRichEntityInterface) {
            return [];
        }

        $stage = $this->getWorkflowStage();

        $locales = $object->getDimensionContents()
            ->filter(
                function(DimensionContentInterface $dimensionContent) use ($stage) {
                    return $stage === $dimensionContent->getStage();
                }
            )
            ->map(
                function(DimensionContentInterface $dimensionContent) {
                    return $dimensionContent->getLocale();
                }
            )->getValues();

        return \array_values(\array_filter(\array_unique($locales)));
    }

    /**
     * @return object|DimensionContentInterface|null
     */
    public function translateObject($object, $locale)
    {
        if (!$object instanceof ContentRichEntityInterface) {
            return $object;
        }

        $stage = $this->getWorkflowStage();

        try {
            $dimensionContent = $this->contentAggregator->aggregate(
                $object,
                [
                    'locale' => $locale,
                    'stage' => $stage,
                ]
            );
        } catch (ContentNotFoundException $e) { // @codeCoverageIgnore
            // TODO FIXME add testcase for this
            return null; // @codeCoverageIgnore
        }

        if ($stage !== $dimensionContent->getStage()
            || $locale !== $dimensionContent->getLocale()) {
            return null;
        }

        return $dimensionContent;
    }

    private function getWorkflowStage(): string
    {
        $interfaces = \class_implements($this->getDimensionContentClass());

        if ($interfaces && \in_array(WorkflowInterface::class, $interfaces, true)
            && SuluKernel::CONTEXT_WEBSITE === $this->context) {
            return DimensionContentInterface::STAGE_LIVE;
        }

        return DimensionContentInterface::STAGE_DRAFT;
    }

    /**
     * @return class-string<B>
     */
    private function getDimensionContentClass(): string
    {
        if (null !== $this->dimensionContentClass) {
            // TODO FIXME add testcase for this
            return $this->dimensionContentClass; // @codeCoverageIgnore
        }

        $this->dimensionContentClass = $this->contentMetadataInspector->getDimensionContentClass($this->contentRichEntityClass);

        return $this->dimensionContentClass;
    }
}
