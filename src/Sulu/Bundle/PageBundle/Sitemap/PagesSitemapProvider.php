<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Sitemap;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Sitemap\AbstractSitemapProvider;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;

/**
 * Provides sitemap for webspaces.
 */
class PagesSitemapProvider extends AbstractSitemapProvider
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var ?AccessControlManagerInterface
     */
    private $accessControlManager;

    public function __construct(
        ContentRepositoryInterface $contentRepository,
        WebspaceManagerInterface $webspaceManager,
        string $environment,
        ?AccessControlManagerInterface $accessControlManager = null
    ) {
        $this->contentRepository = $contentRepository;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->accessControlManager = $accessControlManager;
    }

    public function build($page, $scheme, $host)
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByHostIncludingSubdomains(
            $host, $this->environment
        );

        $result = [];

        foreach ($portalInformations as $portalInformation) {
            $localization = $portalInformation->getLocalization();

            if (!$localization) {
                continue;
            }

            $pages = $this->contentRepository->findAllByPortal(
                $portalInformation->getLocalization()->getLocale(),
                $portalInformation->getPortalKey(),
                MappingBuilder::create()
                    ->addProperties(['changed', 'seo-hideInSitemap'])
                    ->setResolveUrl(true)
                    ->setHydrateGhost(false)
                    ->getMapping()
            );

            foreach ($pages as $contentPage) {
                if (!$contentPage->getUrl()
                    || true === $contentPage['seo-hideInSitemap']
                    || RedirectType::NONE !== $contentPage->getNodeType()
                ) {
                    continue;
                }

                if ($this->accessControlManager) {
                    $userPermissions = $this->accessControlManager->getUserPermissionByArray(
                        $contentPage->getLocale(),
                        PageAdmin::SECURITY_CONTEXT_PREFIX . $contentPage->getWebspaceKey(),
                        $contentPage->getPermissions(),
                        null
                    );

                    if (isset($userPermissions['view']) && !$userPermissions['view']) {
                        continue;
                    }
                }

                $sitemapUrl = $this->generateSitemapUrl($contentPage, $portalInformation, $host, $scheme);

                if (!$sitemapUrl) {
                    continue;
                }

                $result[] = $sitemapUrl;
            }
        }

        return $result;
    }

    private function generateSitemapUrl(
        Content $contentPage,
        PortalInformation $portalInformation,
        string $host,
        string $scheme
    ) {
        $changed = $contentPage['changed'];
        if (\is_string($changed)) {
            $changed = new \DateTime($changed);
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $contentPage->getUrl(),
            $this->environment,
            $contentPage->getLocale(),
            $portalInformation->getWebspaceKey(),
            $host,
            $scheme
        );

        if (!$url) {
            return null;
        }

        $defaultLocale = $portalInformation
            ->getWebspace()
            ->getDefaultLocalization()
            ->getLocale(Localization::DASH);

        $sitemapUrl = new SitemapUrl(
            $url,
            $contentPage->getLocale(),
            $defaultLocale,
            $changed
        );

        foreach ($contentPage->getUrls() as $urlLocale => $href) {
            if (null === $href) {
                continue;
            }

            $url = $this->webspaceManager->findUrlByResourceLocator(
                $href,
                $this->environment,
                $urlLocale,
                $portalInformation->getWebspaceKey(),
                $host,
                $scheme
            );

            if (!$url) {
                continue;
            }

            $sitemapUrl->addAlternateLink(new SitemapAlternateLink($url, $urlLocale));
        }

        return $sitemapUrl;
    }

    public function getAlias(): string
    {
        return 'pages';
    }
}
