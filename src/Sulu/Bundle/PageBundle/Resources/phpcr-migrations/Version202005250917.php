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

class Version202005250917 implements VersionInterface, ContainerAwareInterface
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

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:snippet" OR [jcr:mixinTypes] = "sulu:page") OR [jcr:mixinTypes] = "sulu:home"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = \json_decode($property->getValue(), true);

                    // decide if property is of type smart_content type by checking for presentAs and sortBy
                    if (\is_array($propertyValue) && \array_key_exists('presentAs', $propertyValue) && \array_key_exists('sortBy', $propertyValue)) {
                        if (\is_array($propertyValue['sortBy'])) {
                            $propertyValue['sortBy'] = \count($propertyValue['sortBy']) > 0 ? $propertyValue['sortBy'][0] : null;
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

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:snippet" OR [jcr:mixinTypes] = "sulu:page") OR [jcr:mixinTypes] = "sulu:home"';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            foreach ($node->getProperties() as $property) {
                if (\is_string($property->getValue())) {
                    $propertyValue = \json_decode($property->getValue(), true);

                    // decide if property is of type smart_content type by checking for presentAs and sortBy
                    if (\is_array($propertyValue) && \array_key_exists('presentAs', $propertyValue) && \array_key_exists('sortBy', $propertyValue)) {
                        if (!\is_array($propertyValue['sortBy'])) {
                            $propertyValue['sortBy'] = [$propertyValue['sortBy']];
                        }

                        $property->setValue(\json_encode($propertyValue));
                    }
                }
            }
        }
    }
}
