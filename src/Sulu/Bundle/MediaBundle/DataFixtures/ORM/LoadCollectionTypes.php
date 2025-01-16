<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

/**
 * @final
 *
 * @internal This is an internal class which should not be used by a project.
 *           Instead Create your own fixtures file instead.
 */
class LoadCollectionTypes extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // set id manually
        $metadata = $manager->getClassMetaData(CollectionType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $defaultCollectionType = $manager->find(CollectionType::class, 1);
        if (null === $defaultCollectionType) {
            // create or update collectiontype with id 1
            $defaultCollectionType = new CollectionType();
            $defaultCollectionType->setId(1);
            $manager->persist($defaultCollectionType);
            $defaultCollectionType->setKey('collection.default');
            $defaultCollectionType->setName('Default');
        }

        $systemCollectionType = $manager->find(CollectionType::class, 2);
        if (null === $systemCollectionType) {
            // create or update collectiontype with id 2
            $systemCollectionType = new CollectionType();
            $systemCollectionType->setId(2);
            $manager->persist($systemCollectionType);
            $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
            $systemCollectionType->setName('System Collections');
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 3;
    }
}
