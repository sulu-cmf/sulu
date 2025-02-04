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

namespace Sulu\Content\Infrastructure\Doctrine;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\SeoInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;

/**
 * @internal
 */
final class MetadataLoader
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        /** @var ClassMetadataInfo<object> $metadata */
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();
        $tableName = $metadata->getTableName();

        if ($reflection->implementsInterface(DimensionContentInterface::class)) {
            $this->addField($metadata, 'stage', 'string', ['length' => 15, 'nullable' => false]);
            $this->addField($metadata, 'locale', 'string', ['length' => 15, 'nullable' => true]);
            $this->addField($metadata, 'ghostLocale', 'string', ['length' => 15, 'nullable' => true]);
            $this->addField($metadata, 'availableLocales', 'json', ['nullable' => true, 'options' => ['jsonb' => true]]);
            $this->addIndex($metadata, 'dimension', ['stage', 'locale']);
            $this->addIndex($metadata, 'locale', ['locale']);
            $this->addIndex($metadata, 'stage', ['stage']);
        }

        if ($reflection->implementsInterface(ShadowInterface::class)) {
            $this->addField($metadata, 'shadowLocale', 'string', ['length' => 15, 'nullable' => true]);
            $this->addField($metadata, 'shadowLocales', 'json', ['nullable' => true, 'options' => ['jsonb' => true]]);
        }

        if ($reflection->implementsInterface(TemplateInterface::class)) {
            $this->addField($metadata, 'templateKey', 'string', ['length' => 31]);
            $this->addField($metadata, 'templateData', 'json', ['nullable' => false, 'options' => ['jsonb' => true]]);

            $this->addIndex($metadata, 'template_key', ['templateKey']);
        }

        if ($reflection->implementsInterface(SeoInterface::class)) {
            $this->addField($metadata, 'seoTitle');
            $this->addField($metadata, 'seoDescription', 'text');
            $this->addField($metadata, 'seoKeywords', 'text');
            $this->addField($metadata, 'seoCanonicalUrl', 'text');
            $this->addField($metadata, 'seoNoIndex', 'boolean');
            $this->addField($metadata, 'seoNoFollow', 'boolean');
            $this->addField($metadata, 'seoHideInSitemap', 'boolean');
        }

        if ($reflection->implementsInterface(ExcerptInterface::class)) {
            $this->addField($metadata, 'excerptTitle');
            $this->addField($metadata, 'excerptMore', 'string', ['length' => 63]);
            $this->addField($metadata, 'excerptDescription', 'text');
            $this->addField($metadata, 'excerptImageId', 'integer', [
                'columnName' => 'excerptImageId',
                '_custom' => [
                    'references' => [
                        'entity' => MediaInterface::class,
                        'field' => 'id',
                        'onDelete' => 'SET NULL',
                    ],
                ],
            ]);

            $this->addField($metadata, 'excerptIconId', 'integer', [
                'columnName' => 'excerptIconId',
                '_custom' => [
                    'references' => [
                        'entity' => MediaInterface::class,
                        'field' => 'id',
                        'onDelete' => 'SET NULL',
                    ],
                ],
            ]);

            $this->addManyToMany($event, $metadata, 'excerptTags', TagInterface::class, 'tag_id');
            $this->addManyToMany($event, $metadata, 'excerptCategories', CategoryInterface::class, 'category_id');
        }

        if ($reflection->implementsInterface(WebspaceInterface::class)) {
            $this->addField($metadata, 'mainWebspace', 'string', ['nullable' => true]);
        }

        if ($reflection->implementsInterface(AuthorInterface::class)) {
            $this->addField($metadata, 'authored', 'datetime_immutable', ['nullable' => true]);
            $this->addField($metadata, 'lastModified', 'datetime_immutable', ['nullable' => true]);
            $this->addManyToOne($event, $metadata, 'author', ContactInterface::class, true);
        }

        if ($reflection->implementsInterface(WorkflowInterface::class)) {
            $this->addField($metadata, 'workflowPlace', 'string', ['length' => 32, 'nullable' => true]);
            $this->addField($metadata, 'workflowPublished', 'datetime_immutable', ['nullable' => true]);

            $this->addIndex($metadata, 'workflow_place', ['workflowPlace']);
            $this->addIndex($metadata, 'workflow_published', ['workflowPublished']);
        }
    }

    /**
     * @codeCoverageIgnore
     *
     * @param ClassMetadataInfo<object> $metadata
     * @param class-string $class
     */
    private function addManyToOne(
        LoadClassMetadataEventArgs $event,
        ClassMetadataInfo $metadata,
        string $name,
        string $class,
        bool $nullable = false
    ): void {
        if ($metadata->hasAssociation($name)) {
            return;
        }

        $namingStrategy = $event->getEntityManager()->getConfiguration()->getNamingStrategy();
        $referencedColumnName = $event->getEntityManager()->getClassMetadata($class)->getIdentifierColumnNames()[0];

        $metadata->mapManyToOne([
            'fieldName' => $name,
            'targetEntity' => $class,
            'joinColumns' => [
                [
                    'name' => $namingStrategy->joinKeyColumnName($name), // @phpstan-ignore-line
                    'referencedColumnName' => $referencedColumnName,
                    'nullable' => $nullable,
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE',
                ],
            ],
        ]);
    }

    /**
     * @param ClassMetadataInfo<object> $metadata
     * @param class-string $class
     */
    private function addManyToMany(
        LoadClassMetadataEventArgs $event,
        ClassMetadataInfo $metadata,
        string $name,
        string $class,
        string $inverseColumnName
    ): void {
        if ($metadata->hasAssociation($name)) {
            return;
        }

        $namingStrategy = $event->getEntityManager()->getConfiguration()->getNamingStrategy();

        $referencedColumnName = $metadata->getIdentifierColumnNames()[0];
        $inversedReferencedColumnName = $event->getEntityManager()->getClassMetadata($class)->getIdentifierColumnNames()[0];

        $metadata->mapManyToMany([
            'fieldName' => $name,
            'targetEntity' => $class,
            'joinTable' => [
                'name' => $this->getRelationTableName($metadata, $name),
                'joinColumns' => [
                    [
                        'name' => $namingStrategy->joinKeyColumnName($metadata->getName()),
                        'referencedColumnName' => $referencedColumnName,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => 'CASCADE',
                    ],
                ],
                'inverseJoinColumns' => [
                    [
                        'name' => $inverseColumnName,
                        'referencedColumnName' => $inversedReferencedColumnName,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => 'CASCADE',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param ClassMetadataInfo<object> $metadata
     * @param mixed[] $mapping
     */
    private function addField(ClassMetadataInfo $metadata, string $name, string $type = 'string', array $mapping = []): void
    {
        if ($metadata->hasField($name)) {
            return;
        }

        $nullable = true;
        if ('boolean' === $type) {
            $nullable = false;
        }

        $metadata->mapField(\array_merge([
            'fieldName' => $name,
            'columnName' => $name,
            'type' => $type,
            'nullable' => $nullable,
        ], $mapping));
    }

    /**
     * @param ClassMetadataInfo<object> $metadata
     * @param string[] $fields
     */
    private function addIndex(ClassMetadataInfo $metadata, string $name, array $fields): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $tableName = $metadata->getTableName();

        $builder->addIndex($fields, 'idx_' . $tableName . '_' . $name);
    }

    /**
     * @param ClassMetadataInfo<object> $metadata
     */
    private function getRelationTableName(ClassMetadataInfo $metadata, string $relationName): string
    {
        $inflector = InflectorFactory::create()->build();
        $tableNameParts = \explode('_', $metadata->getTableName());
        $singularName = $inflector->singularize($tableNameParts[\count($tableNameParts) - 1]) . '_';
        $tableNameParts[\count($tableNameParts) - 1] = $singularName;

        return \implode('_', $tableNameParts) . $inflector->tableize($relationName);
    }
}
