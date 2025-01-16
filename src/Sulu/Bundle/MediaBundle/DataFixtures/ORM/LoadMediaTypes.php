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
use Sulu\Bundle\MediaBundle\Entity\MediaType;

/**
 * @final
 *
 * @internal This is an internal class which should not be used by a project.
 *           Instead Create your own fixtures file instead.
 */
class LoadMediaTypes extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // set id manually
        $metadata = $manager->getClassMetaData(MediaType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $mediaDocument = $manager->find(MediaType::class, 1);
        if (null === $mediaDocument) {
            $mediaDocument = new MediaType();
            $mediaDocument->setId(1);
            $manager->persist($mediaDocument);
            $mediaDocument->setName('document');
        }

        $mediaImage = $manager->find(MediaType::class, 2);
        if (null === $mediaImage) {
            $mediaImage = new MediaType();
            $mediaImage->setId(2);
            $manager->persist($mediaImage);
            $mediaImage->setName('image');
        }

        $mediaVideo = $manager->find(MediaType::class, 3);
        if (null === $mediaVideo) {
            $mediaVideo = new MediaType();
            $mediaVideo->setId(3);
            $manager->persist($mediaVideo);
            $mediaVideo->setName('video');
        }

        $mediaAudio = $manager->find(MediaType::class, 4);
        if (null === $mediaAudio) {
            $mediaAudio = new MediaType();
            $mediaAudio->setId(4);
            $manager->persist($mediaAudio);
            $mediaAudio->setName('audio');
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 4;
    }
}
