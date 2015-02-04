<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapDumper;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a xml sitemap
 * @package Sulu\Bundle\WebsiteBundle\Controller
 */
class SitemapController extends WebsiteController
{
    /**
     * Returns a rendered xmlsitemap
     * @return Response
     */
    public function indexAction()
    {
        /** @var RequestAnalyzerInterface $requestAnalyzer */
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');
        /** @var SitemapGeneratorInterface $sitemapGenerator */
        $sitemapGenerator = $this->get('sulu_website.sitemap');
        /** @var SitemapDumper $sitemapDumper */
        $sitemapDumper = $this->get('sulu_website.sitemap.dumper');

        $webspace = $requestAnalyzer->getCurrentWebspace();
        $portal = $requestAnalyzer->getCurrentPortal();

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        if ($sitemap = $sitemapDumper->get($webspace->getKey(), $portal->getKey())) {
            $response->setContent($sitemap);
        } else {
            /** @var WebspaceManagerInterface $webspaceManager */
            $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');
            $defaultLocale = $webspaceManager->findPortalByKey($portal->getKey())->getDefaultLocalization();
            $sitemapPages = $sitemapGenerator->generateForPortal($webspace->getKey(), $portal->getKey(), true);
            $sitemap = $sitemapDumper->dump($sitemapPages, $defaultLocale, $webspace->getKey(), $portal->getKey());

            $response->setContent($sitemap);
        }

        return $response;
    }
}
