<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MediaTypeUpdateCommand extends Command
{
    protected static $defaultName = 'sulu:media:type:update';

    /**
     * @var TypeManagerInterface
     */
    private $mediaTypeManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(TypeManagerInterface $mediaTypeManager, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->mediaTypeManager = $mediaTypeManager;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Update all media type by the set configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->entityManager;
        $repo = $em->getRepository('SuluMediaBundle:Media');
        $medias = $repo->findAll();
        $counter = 0;
        /** @var MediaInterface $media */
        foreach ($medias as $media) {
            /** @var File $file */
            foreach ($media->getFiles() as $file) {
                /** @var FileVersion $fileVersion */
                foreach ($file->getFileVersions() as $fileVersion) {
                    if ($fileVersion->getVersion() == $file->getVersion()) {
                        $mediaTypeId = $this->mediaTypeManager->getMediaType($fileVersion->getMimeType());
                        if ($media->getType()->getId() != $mediaTypeId) {
                            $oldType = $media->getType();
                            $newType = $this->mediaTypeManager->get($mediaTypeId);
                            $media->setType($newType);
                            $em->persist($media);
                            ++$counter;
                            $output->writeln(\sprintf('Media with id <comment>%s</comment> change from type <comment>%s</comment> to <comment>%s</comment>', $media->getId(), $oldType->getName(), $newType->getName()));
                        }
                    }
                }
            }
        }

        if ($counter) {
            $em->flush();
            $output->writeln(\sprintf('<info>SUCCESS FULLY UPDATED (%s)</info>', $counter));
        } else {
            $output->writeln('<comment>Nothing to update</comment>');
        }

        return 0;
    }
}
