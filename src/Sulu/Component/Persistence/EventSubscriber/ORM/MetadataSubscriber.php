<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ReflectionService;

/**
 * Doctrine subscriber used to manipulate metadata.
 */
class MetadataSubscriber
{
    /**
     * @var array
     */
    private $classNames;

    /**
     * Constructor.
     *
     * @param array $objects
     */
    public function __construct(protected $objects)
    {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $event->getClassMetadata();

        $this->process($metadata);

        if (!$metadata->isMappedSuperclass) {
            $em = $event->getEntityManager();
            $this->setAssociationMappings(
                $metadata,
                $em->getConfiguration(),
                $em->getMetadataFactory()->getReflectionService()
            );
        } else {
            $this->unsetAssociationMappings($metadata);
        }
    }

    private function process(ClassMetadataInfo|ClassMetadata $metadata)
    {
        foreach ($this->objects as $application => $classes) {
            foreach ($classes as $class) {
                if (isset($class['model']) && $class['model'] === $metadata->getName()) {
                    $metadata->isMappedSuperclass = false;

                    if (isset($class['repository'])) {
                        $metadata->setCustomRepositoryClass($class['repository']);
                    }
                }
            }
        }
    }

    private function setAssociationMappings(
        ClassMetadataInfo|ClassMetadata $metadata,
        Configuration $configuration,
        ReflectionService $reflectionService
    ) {
        if (!\class_exists($metadata->getName())) {
            return;
        }

        foreach (\class_parents($metadata->getName()) as $parent) {
            if (!\in_array($parent, $this->getAllClassNames($configuration))) {
                continue;
            }

            $parentMetadata = new ClassMetadata($parent, $configuration->getNamingStrategy());
            $parentMetadata->initializeReflection($reflectionService);

            $configuration->getMetadataDriverImpl()->loadMetadataForClass($parent, $parentMetadata);
            if (!$parentMetadata->isMappedSuperclass) {
                continue;
            }

            // map relations
            foreach ($parentMetadata->getAssociationMappings() as $key => $value) {
                if ($this->hasRelation($value['type'])) {
                    $value['sourceEntity'] = $metadata->getName();
                    $metadata->associationMappings[$key] = $value;
                }
            }
        }
    }

    private function unsetAssociationMappings(ClassMetadataInfo $metadata)
    {
        foreach ($metadata->getAssociationMappings() as $key => $value) {
            if ($this->hasRelation($value['type'])) {
                unset($metadata->associationMappings[$key]);
            }
        }
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    private function hasRelation($type)
    {
        return \in_array(
            $type,
            [
                ClassMetadataInfo::MANY_TO_MANY,
                ClassMetadataInfo::ONE_TO_MANY,
                ClassMetadataInfo::ONE_TO_ONE,
            ],
            true
        );
    }

    /**
     * @return array
     */
    private function getAllClassNames(Configuration $configuration)
    {
        if (!$this->classNames) {
            $this->classNames = $configuration->getMetadataDriverImpl()->getAllClassNames();
        }

        return $this->classNames;
    }
}
