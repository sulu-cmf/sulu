<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;

/**
 * This subscriber adds the relationship between media and audience target groups if both bundles are registered.
 */
class MediaAudienceTargetingSubscriber
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var $metadata ClassMetadataInfo */
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();

        if ($reflection && FileVersion::class === $reflection->getName()) {
            $metadata->mapManyToMany([
                'fieldName' => 'targetGroups',
                'targetEntity' => TargetGroupInterface::class,
                'joinTable' => [
                    'name' => 'me_file_version_target_groups',
                    'joinColumns' => [
                        [
                            'name' => 'idFileVersions',
                            'referencedColumnName' => 'id',
                            'nullable' => false,
                            'onDelete' => 'CASCADE',
                        ],
                    ],
                    'inverseJoinColumns' => [
                        [
                            'name' => 'idTargetGroups',
                            'referencedColumnName' => 'id',
                            'nullable' => false,
                            'onDelete' => 'CASCADE',
                        ],
                    ],
                ],
            ]);
        }
    }
}
