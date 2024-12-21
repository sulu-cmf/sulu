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

class Version201903271333 implements VersionInterface, ContainerAwareInterface
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
                $propertyValue = $property->getValue();

                if (\is_string($propertyValue)) {
                    if (false !== \strpos($propertyValue, '<sulu:media')) {
                        $propertyValue = \preg_replace_callback(
                            '/<sulu:media (.*?)>(.*?)<\/sulu:media>/',
                            function($match) {
                                return '<sulu-link provider="media" '
                                    . \str_replace('id=', 'href=', $match[1]) . '>'
                                    . $match[2]
                                    . '</sulu-link>';
                            },
                            $propertyValue
                        );
                    }

                    if (false !== \strpos($propertyValue, '<sulu:')) {
                        $propertyValue = \preg_replace(
                            '/<sulu:(.*?) (.*?)>(.*?)<\/sulu:(.*?)>/',
                            '<sulu-$1 $2>$3</sulu-$4>',
                            $propertyValue
                        );
                    }

                    if ($propertyValue !== $property->getValue()) {
                        $property->setValue($propertyValue);
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
                $propertyValue = $property->getValue();

                if (\is_string($propertyValue)) {
                    if (false !== \strpos($propertyValue, '<sulu-link provider="media"')) {
                        $propertyValue = \preg_replace_callback(
                            '/<sulu-link provider="media" href="(.*?)">(.*?)<\/sulu-link>/',
                            function($match) {
                                return '<sulu:media id="' . $match[1] . '">' . $match[2] . '</sulu:media>';
                            },
                            $propertyValue
                        );
                    }

                    if (false !== \strpos($propertyValue, '<sulu-')) {
                        $propertyValue = \preg_replace(
                            '/<sulu-(.*?) (.*?)>(.*?)<\/sulu-(.*?)>/',
                            '<sulu:$1 $2>$3</sulu:$4>',
                            $propertyValue
                        );
                    }

                    if ($propertyValue !== $property->getValue()) {
                        $property->setValue($propertyValue);
                    }
                }
            }
        }
    }
}
