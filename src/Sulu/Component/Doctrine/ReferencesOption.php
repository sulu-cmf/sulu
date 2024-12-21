<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * Adds a "references" option to the Doctrine schema.
 *
 * The "references" option can be used to add foreign key constraints to
 * individual fields in the mapping (not just associations). The option has
 * four settings:
 *
 *  * entity: The fully-qualified class name of the referenced entity
 *  * field: The referenced field
 *  * onDelete: The on-delete behavior (e.g. SET NULL, optional)
 *  * onUpdate: The on-update behavior (e.g. CASCADE, optional)
 *
 * Example:
 *
 * <field name="regionId" type="int">
 *     <options>
 *          <option name="references">
 *             <option name="entity">Oewm\Api\Domain\Region\Region</option>
 *             <option name="field">id</option>
 *             <option name="onDelete">CASCADE</option>
 *             <option name="onUpdate">CASCADE</option>
 *         </option>
 *     </options>
 * </field>
 */
class ReferencesOption
{
    /**
     * The supported options.
     */
    private static $knownOptions = [
        'entity',
        'field',
        'onDelete',
        'onUpdate',
    ];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private array $targetEntityMapping,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $mapping = $classMetadata->getFieldMapping($fieldName);

            if (!isset($mapping['options']['references'])) {
                continue;
            }

            $mapping['_custom']['references'] = $mapping['options']['references'];
            unset($mapping['options']['references']);
            $classMetadata->setAttributeOverride($mapping['fieldName'], $mapping);
        }
    }

    /**
     * Parses the mapping and adds foreign-key constraints for each
     * "references" option found.
     *
     * @param GenerateSchemaTableEventArgs $args The arguments of the
     *                                           ToolEvents::postGenerateSchemaTable
     *                                           event
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();
        $table = $args->getClassTable();

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $mapping = $classMetadata->getFieldMapping($fieldName);

            if (!isset($mapping['_custom']['references'])) {
                continue;
            }

            $referencesOptions = $mapping['_custom']['references'];

            $unknownOptions = \array_diff_key($referencesOptions, \array_flip(self::$knownOptions));

            if (\count($unknownOptions) > 0) {
                throw new \RuntimeException(
                    \sprintf(
                        'Unknown options "%s" in the "references" option in the Doctrine schema of %s::%s.',
                        \implode('", "', \array_keys($unknownOptions)),
                        $classMetadata->getReflectionClass()->getName(),
                        $fieldName
                    )
                );
            }

            if (!isset($referencesOptions['entity'])) {
                throw new \RuntimeException(
                    \sprintf(
                        'Missing option "entity" in the "references" option in the Doctrine schema of %s::%s.',
                        $classMetadata->getReflectionClass()->getName(),
                        $fieldName
                    )
                );
            }

            if (!isset($referencesOptions['field'])) {
                throw new \RuntimeException(
                    \sprintf(
                        'Missing option "field" in the "references" option in the Doctrine schema of %s::%s.',
                        $classMetadata->getReflectionClass()->getName(),
                        $fieldName
                    )
                );
            }

            $localColumnName = $classMetadata->getColumnName($fieldName);

            // we need to use the actual class if the entity is an interface that is mapped by the SuluPersistenceBundle
            $targetEntity = $referencesOptions['entity'];
            if (\array_key_exists($targetEntity, $this->targetEntityMapping)) {
                $targetEntity = $this->targetEntityMapping[$targetEntity];
            }

            /** @var ObjectManager $manager */
            $manager = $this->managerRegistry->getManagerForClass($targetEntity);
            /** @var ClassMetadata $foreignClassMetadata */
            $foreignClassMetadata = $manager->getClassMetadata($targetEntity);

            $foreignTable = $foreignClassMetadata->getTableName();
            $foreignColumnName = $foreignClassMetadata->getColumnName($referencesOptions['field']);
            $options = [];

            if (isset($referencesOptions['onDelete'])) {
                $options['onDelete'] = $referencesOptions['onDelete'];
            }

            if (isset($referencesOptions['onUpdate'])) {
                $options['onUpdate'] = $referencesOptions['onUpdate'];
            }

            $table->addForeignKeyConstraint(
                $foreignTable,
                [$localColumnName],
                [$foreignColumnName],
                $options
            );
        }
    }
}
