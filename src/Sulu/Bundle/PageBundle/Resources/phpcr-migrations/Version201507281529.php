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

use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201507281529 implements VersionInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(?ContainerInterface $container = null): void
    {
        if (null === $container) {
            throw new \RuntimeException('Container is required to run this migration.');
        }

        $this->container = $container;
    }

    /**
     * @return void
     */
    public function up(SessionInterface $session)
    {
        $this->migrateExternalLinks($session);
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $this->migrateExternalLinks($session, false);
    }

    private function migrateExternalLinks(SessionInterface $session, $directionUp = true)
    {
        $workspace = $session->getWorkspace();
        $queryManager = $workspace->getQueryManager();
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        $propertyEncoder = $this->container->get('sulu_document_manager.property_encoder');

        $webspaces = $webspaceManager->getWebspaceCollection();

        /** @var Webspace $webspace */
        foreach ($webspaces as $webspace) {
            foreach ($webspace->getAllLocalizations() as $localization) {
                $locale = $localization->getLocale();

                $query = $queryManager->createQuery(
                    \sprintf(
                        'SELECT * FROM [nt:base] WHERE [%s] = 4 AND [jcr:mixinTypes] = "sulu:page"',
                        $propertyEncoder->localizedSystemName('nodeType', $locale)
                    ),
                    'JCR-SQL2'
                );
                $rows = $query->execute();

                foreach ($rows as $row) {
                    /** @var NodeInterface $node */
                    $node = $row->getNode();
                    $templatePropertyName = $propertyEncoder->localizedSystemName('template', $locale);

                    try {
                        if (true === $directionUp) {
                            $node->setProperty(
                                $templatePropertyName,
                                $webspace->getDefaultTemplate('page')
                            );
                        } else {
                            $node->setProperty($templatePropertyName, 'external-link');
                        }
                    } catch (\Exception $e) {
                        echo $e->getMessage() . \PHP_EOL;
                    }
                }
            }
        }
    }
}
