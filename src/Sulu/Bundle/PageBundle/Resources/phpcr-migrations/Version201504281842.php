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
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201504281842 implements VersionInterface, ContainerAwareInterface
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
        $this->migrateInternalLinks($session);
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $this->migrateInternalLinks($session, false);
    }

    private function migrateInternalLinks(SessionInterface $session, $directionUp = true)
    {
        $workspace = $session->getWorkspace();
        $queryManager = $workspace->getQueryManager();
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        $propertyEncoder = $this->container->get('sulu_document_manager.property_encoder');

        $webspaces = $webspaceManager->getWebspaceCollection();

        foreach ($webspaces as $webspace) {
            foreach ($webspace->getAllLocalizations() as $localization) {
                $locale = $localization->getLocalization();

                $sql = <<<'EOT'
SELECT * FROM [nt:unstructured] WHERE %s = 2
EOT;

                $query = $queryManager->createQuery(\sprintf($sql, '[' . $propertyEncoder->localizedSystemName('nodeType', $locale) . ']'), 'JCR-SQL2');
                $rows = $query->execute();

                foreach ($rows as $row) {
                    /** @var NodeInterface $node */
                    $node = $row->getNode();
                    $internalLinkName = $propertyEncoder->localizedSystemName('internal_link', $locale);

                    try {
                        if (true === $directionUp) {
                            $internalUuid = $node->getPropertyValue($internalLinkName);

                            $internalNode = $session->getNodeByIdentifier($internalUuid);
                            $node->setProperty($internalLinkName, null);
                            $node->setProperty($internalLinkName, $internalNode);
                        } else {
                            $internalNode = $node->getPropertyValue($internalLinkName);

                            $internalNodeUuid = $internalNode->getIdentifier();
                            $node->setProperty($internalLinkName, null);
                            $node->setProperty($internalLinkName, $internalNodeUuid);
                        }
                    } catch (\Exception $e) {
                        echo $e->getMessage() . \PHP_EOL;
                    }
                }
            }
        }
    }
}
