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

namespace Sulu\Content\Infrastructure\Sulu\Preview;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
class ContentObjectProvider implements PreviewObjectProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContentAggregatorInterface
     */
    private $contentAggregator;

    /**
     * @var ContentDataMapperInterface
     */
    private $contentDataMapper;

    /**
     * @var class-string<T>
     */
    private $contentRichEntityClass;

    /**
     * @var string|null
     */
    private $securityContext;

    /**
     * @param class-string<T> $contentRichEntityClass
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContentAggregatorInterface $contentAggregator,
        ContentDataMapperInterface $contentDataMapper,
        string $contentRichEntityClass,
        ?string $securityContext = null
    ) {
        $this->entityManager = $entityManager;
        $this->contentAggregator = $contentAggregator;
        $this->contentDataMapper = $contentDataMapper;
        $this->contentRichEntityClass = $contentRichEntityClass;
        $this->securityContext = $securityContext;
    }

    /**
     * @param string|int $id
     * @param string $locale
     *
     * @return B|null
     */
    public function getObject($id, $locale)
    {
        try {
            /** @var T $contentRichEntity */
            $contentRichEntity = $this->entityManager->createQueryBuilder()
                ->select('entity')
                ->from($this->contentRichEntityClass, 'entity')
                ->where('entity = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }

        return $this->resolveContent($contentRichEntity, $locale);
    }

    /**
     * @param B $object
     *
     * @return string|int
     */
    public function getId($object)
    {
        return $object->getResource()->getId();
    }

    /**
     * @param B $object
     * @param string $locale
     * @param mixed[] $data
     */
    public function setValues($object, $locale, array $data): void
    {
        $previewDimensionContentCollection = new PreviewDimensionContentCollection($object, $locale);
        $this->contentDataMapper->map(
            $previewDimensionContentCollection,
            $previewDimensionContentCollection->getDimensionAttributes(),
            $data
        );
    }

    /**
     * @param B $object
     * @param string $locale
     * @param mixed[] $context
     *
     * @return B
     */
    public function setContext($object, $locale, array $context): DimensionContentInterface
    {
        if ($object instanceof TemplateInterface) {
            if (\array_key_exists('template', $context)) {
                $object->setTemplateKey($context['template']);
            }
        }

        return $object;
    }

    /**
     * @param B $object
     *
     * @return string
     */
    public function serialize($object)
    {
        return \json_encode([
            'id' => $object->getResource()->getId(),
            'locale' => $object->getLocale(),
        ]) ?: '[]';
    }

    /**
     * @param string $serializedObject
     * @param class-string $objectClass
     *
     * @return B|null
     */
    public function deserialize($serializedObject, $objectClass)
    {
        /** @var array{id?: int|string, locale?: string} $data */
        $data = \json_decode($serializedObject, true);

        $id = $data['id'] ?? null;
        $locale = $data['locale'] ?? null;

        if (!$id || !$locale) {
            return null;
        }

        return $this->getObject($id, $locale);
    }

    public function getSecurityContext($id, $locale): ?string
    {
        return $this->securityContext;
    }

    /**
     * @param T $contentRichEntity
     *
     * @return B|null
     */
    protected function resolveContent(ContentRichEntityInterface $contentRichEntity, string $locale): ?DimensionContentInterface
    {
        try {
            $resolvedDimensionContent = $this->contentAggregator->aggregate(
                $contentRichEntity,
                [
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                ]
            );

            // unfortunately we can only check if it is a shadow after the dimensionContent was loaded
            if ($resolvedDimensionContent instanceof ShadowInterface && $resolvedDimensionContent->getShadowLocale()) {
                return $this->resolveContent($contentRichEntity, $resolvedDimensionContent->getShadowLocale());
            }

            if (!$resolvedDimensionContent->getLocale()) {
                // avoid 500 error when ghostLocale is loaded by still use correct locale in serialize method
                $resolvedDimensionContent->setLocale($locale);
            }

            return $resolvedDimensionContent;
        } catch (ContentNotFoundException $exception) {
            return null;
        }
    }
}
