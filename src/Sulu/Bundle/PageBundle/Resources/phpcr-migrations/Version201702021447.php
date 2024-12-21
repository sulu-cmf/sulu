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

use Jackalope\Query\Row;
use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the property `i18n:<locale>-author` and `i18n:<locale>-authored` and prefill it with creator/created.
 */
class Version201702021447 implements VersionInterface, ContainerAwareInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

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
        $this->userRepository = $this->container->get('sulu.repository.user');

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
        $this->userRepository = $this->container->get('sulu.repository.user');

        $this->downgrade($liveSession);
        $this->downgrade($session);

        $liveSession->save();
        $session->save();
    }

    /**
     * Upgrade all nodes in given session.
     */
    private function upgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Row $row */
        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            /** @var Localization $localization */
            foreach ($localizations as $localization) {
                $createdPropertyName = \sprintf('i18n:%s-created', $localization->getLocale());
                if ($node->hasProperty($createdPropertyName)) {
                    $node->setProperty(
                        \sprintf('i18n:%s-authored', $localization->getLocale()),
                        $node->getPropertyValue($createdPropertyName)
                    );
                }

                $creatorPropertyName = \sprintf('i18n:%s-creator', $localization->getLocale());
                if ($node->hasProperty($creatorPropertyName)) {
                    $user = $this->userRepository->findUserById($node->getPropertyValue($creatorPropertyName));

                    if ($user) {
                        $node->setProperty(
                            \sprintf('i18n:%s-author', $localization->getLocale()),
                            $user->getContact()->getId()
                        );
                    }
                }
            }
        }
    }

    /**
     * Downgrades all nodes in given session.
     */
    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Row $row */
        foreach ($rows as $row) {
            /** @var NodeInterface $node */
            $node = $row->getNode();

            /** @var Localization $localization */
            foreach ($localizations as $localization) {
                $authoredPropertyName = \sprintf('i18n:%s-authored', $localization->getLocale());
                if ($node->hasProperty($authoredPropertyName)) {
                    $node->getProperty($authoredPropertyName)->remove();
                }

                $authorPropertyName = \sprintf('i18n:%s-author', $localization->getLocale());
                if ($node->hasProperty($authorPropertyName)) {
                    $node->getProperty($authoredPropertyName)->remove();
                }
            }
        }
    }
}
