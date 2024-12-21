<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle;

use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\Migrations\VersionInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the live workspace, copies the data from the draft workspace, and removes all unpublished data from the live
 * workspace.
 */
class Version201607181533 implements VersionInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @return void
     */
    public function up(SessionInterface $session)
    {
        $defaultSession = $this->container->get('sulu_document_manager.default_session');
        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $session->getWorkspace()->createWorkspace($liveSession->getWorkspace()->getName());
        $queryManager = $liveSession->getWorkspace()->getQueryManager();

        $fileName = \tempnam(\sys_get_temp_dir(), 'sulu-publishing');
        $file = \fopen($fileName, 'w');
        $defaultSession->exportSystemView('/cmf', $file, false, false);
        $liveSession->importXML('/', $fileName, ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_THROW);

        $liveSession->save();

        $query = 'SELECT * FROM [nt:unstructured] WHERE [jcr:mixinTypes] = "sulu:page" AND [i18n:%s-state] = 1';
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        foreach ($localizations as $localization) {
            $rows = $queryManager
                ->createQuery(\sprintf($query, $localization->getLocale()), 'JCR-SQL2')
                ->execute();

            $propertyPrefix = 'i18n:' . $localization->getLocale() . '-*';

            $i = 0;
            foreach ($rows->getNodes() as $node) {
                foreach ($node->getProperties($propertyPrefix) as $property) {
                    $property->remove();
                }

                if (++$i > 10) {
                    $liveSession->save();
                }
            }
        }

        $liveSession->save();

        \unlink($fileName);
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $session->getWorkspace()->deleteWorkspace($liveSession->getWorkspace()->getName());
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(?ContainerInterface $container = null): void
    {
        if (null === $container) {
            throw new \RuntimeException('Container is required to run this migration.');
        }

        $this->container = $container;
    }
}
