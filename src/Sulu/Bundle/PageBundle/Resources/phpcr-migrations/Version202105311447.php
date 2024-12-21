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

class Version202105311447 implements VersionInterface, ContainerAwareInterface
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
        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $this->upgrade($liveSession);
        $this->upgrade($session);

        $liveSession->save();
        $session->save();
    }

    /**
     * @return void
     */
    public function down(SessionInterface $session)
    {
        $liveSession = $this->container->get('sulu_document_manager.live_session');

        $this->downgrade($liveSession);
        $this->downgrade($session);

        $liveSession->save();
        $session->save();
    }

    /**
     * @return void
     */
    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page") OR [jcr:mixinTypes] = "sulu:home"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            $permissions = [];
            foreach ($node->getProperties('sec:role-*') as $property) {
                $roleId = (int) \str_replace('sec:role-', '', $property->getName());

                $rolePermissions = $property->getValue();
                if (!$rolePermissions) {
                    $rolePermissions = [];
                }

                $permissions[$roleId] = $rolePermissions;
            }

            $node->setProperty('sec:permissions', \json_encode($permissions));
        }
    }

    /**
     * @return void
     */
    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page") OR [jcr:mixinTypes] = "sulu:home"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            if (!$node->hasProperty('sec:permissions')) {
                continue;
            }

            $node->getProperty('sec:permissions')->remove();
        }
    }
}
