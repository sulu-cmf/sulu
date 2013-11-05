<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

use Psr\Log\LoggerInterface;
use Sulu\Component\Portal\Loader\XmlFileLoader;
use Sulu\Component\Portal\PortalManager;

class PortalManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    /**
     * @var PortalManager
     */
    protected $portalManager;

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

        $this->portalManager = new PortalManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Portal/valid'
            )
        );
    }

    public function tearDown()
    {
        if (file_exists(__DIR__ . '/../../../Resources/cache/PortalCollectionCache.php')) {
            unlink(__DIR__ . '/../../../Resources/cache/PortalCollectionCache.php');
        }
    }

    public function testGetAll()
    {
        $portals = $this->portalManager->getPortals();

        $portal = $portals->get('massiveart');

        $this->assertEquals('Massive Art', $portal->getName());
        $this->assertEquals('massiveart', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('massiveart.com', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.massiveart.com', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());

        $portal = $portals->get('sulu_io');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
    }

    public function testFindByKey()
    {
        $portal = $this->portalManager->findByKey('sulu_io');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
    }

    public function testFindByNotExistingKey()
    {
        $portal = $this->portalManager->findByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindByUrl()
    {
        $portal = $this->portalManager->findByUrl('sulu.io');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());

        $portal = $this->portalManager->findByUrl('sulu.lo');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
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

        $this->portalManager = new PortalManager(
            $this->loader,
            $this->logger,
            array(
                'cache_dir' => __DIR__ . '/../../../Resources/cache',
                'config_dir' => __DIR__ . '/../../../Resources/DataFixtures/Portal/both'
            )
        );

        $portals = $this->portalManager->getPortals();

        $this->assertEquals(2, $portals->length());

        $portal = $portals->get('massiveart');

        $this->assertEquals('Massive Art', $portal->getName());
        $this->assertEquals('massiveart', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('massiveart', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('massiveart.com', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.massiveart.com', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('massiveart.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());

        $portal = $portals->get('sulu_io');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->isMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->isFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->isMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->isFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
    }
}
