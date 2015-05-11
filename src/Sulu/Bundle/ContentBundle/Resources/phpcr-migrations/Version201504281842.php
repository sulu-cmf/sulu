<?php

namespace Sulu\Bundle\ContentBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\HomeNodeType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201504281842 implements VersionInterface, ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(SessionInterface $session)
    {
        $this->migrateInternalLinks($session);
    }

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

                $sql = <<<EOT
SELECT * FROM [nt:unstructured] WHERE %s = 2
EOT
                ;

                $query = $queryManager->createQuery(sprintf($sql, $propertyEncoder->localizedSystemName('nodeType', $locale)), 'JCR-SQL2');
                $rows = $query->execute();

                foreach ($rows as $row) {

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
                        echo $e->getMessage() . PHP_EOL;
                    }

                }
            }
        }
    }
}
