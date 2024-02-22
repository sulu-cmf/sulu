<?php

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Sitemap\ContentSitemapProvider;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ArticleSitemapProvider extends ContentSitemapProvider
{
    public function __construct(EntityManagerInterface $entityManager, WebspaceManagerInterface $webspaceManager, string $kernelEnvironment, string $contentRichEntityClass, string $routeClass, string $alias)
    {
        parent::__construct($entityManager, $webspaceManager, $kernelEnvironment, $contentRichEntityClass, $routeClass, $alias);
    }

    protected function getEntityIdField(): string
    {
        return 'uuid';
    }
}
