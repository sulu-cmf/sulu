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

class Version201905071542 implements VersionInterface, ContainerAwareInterface
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

    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = \json_decode($property->getValue(), true);
                    if ($propertyValue
                        && \is_array($propertyValue)
                        && \array_key_exists('items', $propertyValue)
                        && \array_key_exists('displayOption', $propertyValue)
                    ) {
                        unset($propertyValue['displayOption']);
                        foreach ($propertyValue['items'] as &$item) {
                            if ('content' === $item['type']) {
                                $item['type'] = 'pages';
                            }
                        }

                        $property->setValue(\json_encode($propertyValue));
                    }
                }
            }
        }
    }

    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = \json_decode($property->getValue(), true);
                    if ($propertyValue
                        && \is_array($propertyValue)
                        && \array_key_exists('items', $propertyValue)
                    ) {
                        $propertyValue['displayOption'] = 'top';
                        foreach ($propertyValue['items'] as &$item) {
                            if ('pages' === $item['type']) {
                                $item['type'] = 'content';
                            }
                        }

                        $property->setValue(\json_encode($propertyValue));
                    }
                }
            }
        }
    }
}
