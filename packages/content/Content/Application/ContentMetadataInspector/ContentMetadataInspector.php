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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentMetadataInspector;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentMetadataInspector implements ContentMetadataInspectorInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param class-string<ContentRichEntityInterface<T>> $contentRichEntityClass
     *
     * @return class-string<T>
     */
    public function getDimensionContentClass(string $contentRichEntityClass): string
    {
        $contentRichEntityClass = ClassUtils::getRealClass($contentRichEntityClass);

        $classMetadata = $this->entityManager->getClassMetadata($contentRichEntityClass);
        /** @var array{targetEntity: class-string<T>} $associationMapping */
        $associationMapping = $classMetadata->getAssociationMapping('dimensionContents');

        return $associationMapping['targetEntity'];
    }

    public function getDimensionContentPropertyName(string $contentRichEntityClass): string
    {
        $contentRichEntityClass = ClassUtils::getRealClass($contentRichEntityClass);

        $classMetadata = $this->entityManager->getClassMetadata($contentRichEntityClass);
        /** @var array{mappedBy: string} $associationMapping */
        $associationMapping = $classMetadata->getAssociationMapping('dimensionContents');

        return $associationMapping['mappedBy'];
    }
}
