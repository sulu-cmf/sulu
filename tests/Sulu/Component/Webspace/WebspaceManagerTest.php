<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Psr\Log\LoggerInterface;
use Sulu\Component\Webspace\Loader\XmlFileLoader;
use Sulu\Component\Webspace\Manager\WebspaceManager;

class WebspaceManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    /**
     * @var WebspaceManager
     */
    protected $webspaceManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setUp()
    {
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', array('locate'));
        $locator->expects($this->any())->method('locate')->will($this->returnArgument(0));
        $this->loader = new XmlFileLoader($locator);

        $this->logger = $this->getMock('\Psr\Log\LoggerInterface');

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Webspace/valid'
            )
        );
    }

    public function tearDown()
    {
        if (file_exists(__DIR__ . '/../../../Resources/cache/WebspaceCollectionCache.php')) {
            unlink(__DIR__ . '/../../../Resources/cache/WebspaceCollectionCache.php');
        }
    }

    public function testGetAll()
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $webspace = $webspaces->getWebspace('massiveart');

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());

        $this->assertEquals(1, count($webspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('fr', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('massiveart', $webspace->getTheme()->getKey());
        $this->assertEquals(1, count($webspace->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $webspace->getTheme()->getExcludedTemplates()[0]);

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());


        $portal = $webspace->getPortals()[1];

        $this->assertEquals('Massive Art CA', $portal->getName());
        $this->assertEquals('massiveart_ca', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[0]->getRedirect());
        $this->assertEquals('www.massiveart.com', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals('en', $portal->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('s', $portal->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $portal->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());

    }

    public function testFindWebspaceByKey()
    {
        $webspace = $this->webspaceManager->findWebspaceByKey('sulu_io');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());

        $this->assertEquals(2, count($webspace->getLocalizations()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('sulu', $webspace->getTheme()->getKey());
        $this->assertEquals(1, count($webspace->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $webspace->getTheme()->getExcludedTemplates()[0]);

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindPortalByKey()
    {
        $portal = $this->webspaceManager->findPortalByKey('sulucmf_at');

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindWebspaceByNotExistingKey()
    {
        $portal = $this->webspaceManager->findWebspaceByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalByNotExistingKey()
    {
        $portal = $this->webspaceManager->findPortalByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalInformationByUrl()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.at/test/test/test', 'prod');
        $this->assertEquals('de_at', $portalInformation['localization']->getLocalization());
        $this->assertArrayNotHasKey('segment', $portalInformation);

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertEquals('de_at', $portalInformation['localization']->getLocalization());
        $this->assertArrayNotHasKey('segment', $portalInformation);

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals('short', $portal->getResourceLocatorStrategy());

        $this->assertEquals(1, count($portal->getLocalizations()));
        $this->assertEquals('de', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testFindPortalInformationByUrlWithSegment()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('en.massiveart.us/w/about-us', 'prod');
        $this->assertEquals('en_us', $portalInformation['localization']->getLocalization());
        $this->assertEquals('winter', $portalInformation['segment']->getName());

        /** @var Portal $portal */
        $portal = $portalInformation['portal'];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals('tree', $portal->getResourceLocatorStrategy());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    public function testInvalidPart()
    {
        $this->logger = $this->getMockForAbstractClass(
            '\Psr\Log\LoggerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('warning')
        );

        $this->logger->expects($this->once())->method('warning')->will($this->returnValue(null));

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Webspace/both'
            )
        );

        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $this->assertEquals(3, $webspaces->length());

        $webspace = $webspaces->getWebspace('massiveart');

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());

        $webspace = $webspaces->getWebspace('sulu_io');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
    }

    public function testRedirectUrl()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('www.sulu.at/test/test', 'prod');

        $this->assertEquals('sulu.at', $portalInformation['redirect']);
        $this->assertEquals('www.sulu.at', $portalInformation['url']);
    }

    public function testLocalizations()
    {
        $localizations = $this->webspaceManager->findWebspaceByKey('massiveart')->getLocalizations();
        
        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertEquals('us', $localizations[0]->getCountry());
        $this->assertEquals('auto', $localizations[0]->getShadow());

        $this->assertEquals(1, count($localizations[0]->getChildren()));
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $localizations[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $localizations[0]->getChildren()[0]->getShadow());
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getParent()->getLanguage());
        $this->assertEquals('us', $localizations[0]->getChildren()[0]->getParent()->getCountry());
        $this->assertEquals('auto', $localizations[0]->getChildren()[0]->getParent()->getShadow());

        $this->assertEquals('fr', $localizations[1]->getLanguage());
        $this->assertEquals('ca', $localizations[1]->getCountry());
        $this->assertEquals(null, $localizations[1]->getShadow());

        $allLocalizations = $this->webspaceManager->findWebspaceByKey('massiveart')->getAllLocalizations();
        $this->assertEquals('en', $allLocalizations[0]->getLanguage());
        $this->assertEquals('us', $allLocalizations[0]->getCountry());
        $this->assertEquals('auto', $allLocalizations[0]->getShadow());
        $this->assertEquals('en', $allLocalizations[1]->getLanguage());
        $this->assertEquals('ca', $allLocalizations[1]->getCountry());
        $this->assertEquals(null, $allLocalizations[1]->getShadow());
        $this->assertEquals('fr', $allLocalizations[2]->getLanguage());
        $this->assertEquals('ca', $allLocalizations[2]->getCountry());
        $this->assertEquals(null, $allLocalizations[2]->getShadow());
    }
}
