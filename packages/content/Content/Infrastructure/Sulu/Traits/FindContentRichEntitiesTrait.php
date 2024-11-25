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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;

/**
 * @template D of ContentRichEntityInterface
 *
 * @internal
 */
trait FindContentRichEntitiesTrait
{
    /**
     * @param string[]|int[] $ids
     *
     * @return D[]
     */
    protected function findEntitiesByIds(array $ids): array
    {
        $entityIdField = $this->getEntityIdField();
        $entityManager = $this->getEntityManager();
        $contentRichEntityClass = $this->getContentRichEntityClass();
        $classMetadata = $entityManager->getClassMetadata($contentRichEntityClass);

        /** @var D[] $entities */
        $entities = $entityManager->createQueryBuilder()
            ->select(ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY)
            ->from($contentRichEntityClass, ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY)
            ->where(ContentWorkflowInterface::CONTENT_RICH_ENTITY_CONTEXT_KEY . '.' . $entityIdField . ' IN (:ids)')
            ->getQuery()
            ->setParameter('ids', $ids)
            ->getResult();

        $idPositions = \array_flip($ids);

        \usort(
            $entities,
            function(ContentRichEntityInterface $a, ContentRichEntityInterface $b) use ($idPositions, $classMetadata, $entityIdField) {
                $aId = $classMetadata->getIdentifierValues($a)[$entityIdField];
                $bId = $classMetadata->getIdentifierValues($b)[$entityIdField];

                return $idPositions[$aId] - $idPositions[$bId];
            }
        );

        return $entities;
    }

    abstract protected function getEntityIdField(): string;

    /**
     * @return class-string<D>
     */
    abstract protected function getContentRichEntityClass(): string;

    abstract protected function getEntityManager(): EntityManagerInterface;
}
