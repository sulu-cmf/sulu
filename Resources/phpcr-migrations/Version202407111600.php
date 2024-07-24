<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle;

use Jackalope\Query\Row;
use PHPCR\Migrations\VersionInterface;
use PHPCR\PhpcrMigrationsBundle\ContainerAwareInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ArticleBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version202407111600 implements VersionInterface, ContainerAwareInterface
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $metadataFactory;

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

    public function up(SessionInterface $session)
    {
        $this->propertyEncoder = $this->container->get('sulu_document_manager.property_encoder');
        $this->metadataFactory = $this->container->get('sulu_page.structure.factory');

        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $this->upgrade($liveSession);
        $this->upgrade($session);

        $liveSession->save();
        $session->save();
    }

    public function down(SessionInterface $session)
    {
        $this->propertyEncoder = $this->container->get('sulu_document_manager.property_encoder');
        $this->metadataFactory = $this->container->get('sulu_page.structure.factory');

        $liveSession = $this->container->get('sulu_document_manager.live_session');
        $this->downgrade($liveSession);
        $this->downgrade($session);

        $liveSession->save();
        $session->save();
    }

    private function upgrade(SessionInterface $session): void
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:article" OR [jcr:mixinTypes] = "sulu:articlepage")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Localization $localization */
        foreach ($localizations as $localization) {
            $locale = $localization->getLocale();
            $templateKey = $this->propertyEncoder->localizedContentName('template', $locale);

            /** @var Row<mixed> $row */
            foreach ($rows as $row) {
                $node = $row->getNode();
                $structureType = $node->getPropertyValue($templateKey);
                $routePathPropertyName = $this->getRoutePathPropertyName($structureType, $locale);

                $propertyName = $this->propertyEncoder->localizedContentName(RoutableSubscriber::ROUTE_FIELD_NAME, $locale);
                $node->setProperty($propertyName, $routePathPropertyName);
            }
        }
    }

    private function downgrade(SessionInterface $session)
    {
        $queryManager = $session->getWorkspace()->getQueryManager();
        $localizations = $this->container->get('sulu_core.webspace.webspace_manager')->getAllLocalizations();

        $query = 'SELECT * FROM [nt:unstructured] WHERE ([jcr:mixinTypes] = "sulu:article" OR [jcr:mixinTypes] = "sulu:articlepage")';
        $rows = $queryManager->createQuery($query, 'JCR-SQL2')->execute();

        /** @var Localization $localization */
        foreach ($localizations as $localization) {
            $locale = $localization->getLocale();

            /** @var Row<mixed> $row */
            foreach ($rows as $row) {
                $node = $row->getNode();
                $propertyName = $this->propertyEncoder->localizedContentName(RoutableSubscriber::ROUTE_FIELD_NAME, $locale);
                $node->setProperty($propertyName, null);
            }
        }
    }

    private function getRoutePathPropertyName(string $structureType, string $locale): string
    {
        $metadata = $this->metadataFactory->getStructureMetadata('article', $structureType);

        if ($metadata->hasPropertyWithTagName(RoutableSubscriber::TAG_NAME)) {
            return $this->getPropertyName($locale, $metadata->getPropertyByTagName(RoutableSubscriber::TAG_NAME)->getName());
        }

        return $this->getPropertyName($locale, RoutableSubscriber::ROUTE_FIELD);
    }

    private function getPropertyName(string $locale, string $field): string
    {
        return $this->propertyEncoder->localizedSystemName($field, $locale);
    }
}
