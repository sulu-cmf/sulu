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

use Jackalope\Property;
use PHPCR\Migrations\VersionInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\NodeTypes\Content\HomeNodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201504271608 implements VersionInterface, ContainerAwareInterface
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
        $workspace = $session->getWorkspace();
        $nodeTypeManager = $workspace->getNodeTypeManager();
        $nodeTypeManager->registerNodeType(
            new HomeNodeType(),
            true
        );

        $this->migrateHome($session, 'sulu:page', 'sulu:home', true);
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $this->migrateHome($session, 'sulu:home', 'sulu:page', false);
    }

    private function migrateHome(SessionInterface $session, $from, $to, $referenceWebspace)
    {
        $webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        $pathRegistry = $this->container->get('sulu_document_manager.path_segment_registry');

        $webspaces = $webspaceManager->getWebspaceCollection();

        foreach ($webspaces as $webspace) {
            $webspacePath = \sprintf('/%s/%s', $pathRegistry->getPathSegment('base'), $webspace->getKey());
            $homeNodeName = $pathRegistry->getPathSegment('content');
            $webspace = $session->getNode($webspacePath);

            if ($referenceWebspace) {
                $webspace->addMixin('mix:referenceable');
            } else {
                $webspace->removeMixin('mix:referenceable');
            }

            $homeNode = $webspace->getNode($homeNodeName);
            $tmpNode = $session->getRootNode()->addNode('/tmp');
            $tmpNode->addMixin('mix:referenceable');

            $session->save();

            $homeNodeReferences = $homeNode->getReferences();
            $homeNodeReferenceValues = [];
            foreach ($homeNodeReferences as $homeNodeReference) {
                /* @var Property $homeNodeReference */
                $homeNodeReferenceValues[$homeNodeReference->getPath()] = $homeNodeReference->getValue();
                $homeNodeReference->setValue($tmpNode);
            }

            $session->save();
            $homeNode->removeMixin($from);
            $session->save();
            $homeNode->addMixin($to);
            $session->save();

            foreach ($homeNodeReferences as $homeNodeReference) {
                $homeNodeReference->setValue(
                    $homeNodeReferenceValues[$homeNodeReference->getPath()],
                    PropertyType::REFERENCE
                );
            }

            $session->save();

            $tmpNode->remove();
        }
    }
}
