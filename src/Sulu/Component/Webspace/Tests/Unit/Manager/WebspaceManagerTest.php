<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Exception\InvalidTemplateException;
use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebspaceManagerTest extends WebspaceTestCase
{
    use ProphecyTrait;

    protected DelegatingLoader $loader;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    protected WebspaceManager $webspaceManager;

    private string $cacheDirectory;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    public function setUp(): void
    {
        $this->cacheDirectory = $this->getResourceDirectory() . '/cache';

        if (\file_exists($this->cacheDirectory)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->cacheDirectory);
        }

        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(function($arguments) {
            return $arguments[0];
        });

        $resolver = new LoaderResolver([
            new XmlFileLoader11($locator->reveal()),
            new XmlFileLoader10($locator->reveal()),
        ]);

        $this->loader = new DelegatingLoader($resolver);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $defaultStructure = new StructureMetadata('default');
        $overviewStructure = new StructureMetadata('overview');
        $this->structureMetadataFactory->getStructures('page')->willReturn([$defaultStructure, $overviewStructure]);

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            $this->requestStack->reveal(),
            [
                'cache_dir' => $this->cacheDirectory,
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/valid',
                'cache_class' => 'WebspaceCollectionCache' . \uniqid(),
            ],
            'test',
            'sulu.io',
            'http',
            $this->structureMetadataFactory->reveal()
        );
    }

    public function testGetAll(): void
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $webspace = $webspaces->getWebspace('massiveart');
        $this->assertInstanceOf(Webspace::class, $webspace);
        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());
        $this->assertEquals('massiveart', $webspace->getSecurity()?->getSystem());

        $webspaceLocalizations = $webspace->getLocalizations();
        $this->assertCount(3, $webspaceLocalizations);
        $this->assertEquals('en', $webspaceLocalizations[0]->getLanguage());
        $this->assertEquals('us', $webspaceLocalizations[0]->getCountry());
        $this->assertEquals('auto', $webspaceLocalizations[0]->getShadow());

        $this->assertEquals(1, \count($webspaceLocalizations[0]->getChildren()));
        $this->assertEquals('en', $webspaceLocalizations[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $webspaceLocalizations[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspaceLocalizations[0]->getChildren()[0]->getShadow());

        $this->assertEquals('fr', $webspaceLocalizations[1]->getLanguage());
        $this->assertEquals('ca', $webspaceLocalizations[1]->getCountry());
        $this->assertEquals(null, $webspaceLocalizations[1]->getShadow());

        $this->assertEquals('massiveart', $webspace->getTheme());

        $webspaceNavigationContext = $webspace->getNavigation()->getContexts();
        $this->assertEquals(2, \count($webspaceNavigationContext));
        $this->assertEquals('main', $webspaceNavigationContext[0]->getKey());
        $this->assertEquals('Hauptnavigation', $webspaceNavigationContext[0]->getTitle('de'));
        $this->assertEquals('Mainnavigation', $webspaceNavigationContext[0]->getTitle('en'));
        $this->assertEquals('Main', $webspaceNavigationContext[0]->getTitle('fr'));

        $this->assertEquals('footer', $webspaceNavigationContext[1]->getKey());
        $this->assertEquals('Unten', $webspaceNavigationContext[1]->getTitle('de'));
        $this->assertEquals('Footer', $webspaceNavigationContext[1]->getTitle('en'));
        $this->assertEquals('Footer', $webspaceNavigationContext[1]->getTitle('fr'));

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals(4, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('en', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[2]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[2]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[2]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[3]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[3]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[3]->getShadow());

        $this->assertEquals(2, \count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals('{language}.massiveart.{country}', $environmentProd->getUrls()[0]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('massiveart.lo/{localization}', $environmentDev->getUrls()[0]->getUrl());

        $portal = $webspace->getPortals()[1];

        $this->assertEquals('Massive Art CA', $portal->getName());
        $this->assertEquals('massiveart_ca', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(2, \count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertEquals(2, \count($environmentProd->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getCountry());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getRedirect());
        $this->assertEquals('www.massiveart.com', $environmentProd->getUrls()[1]->getUrl());
        $this->assertEquals('en', $environmentProd->getUrls()[1]->getLanguage());
        $this->assertEquals('ca', $environmentProd->getUrls()[1]->getCountry());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getRedirect());

        $environmentProd = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals('massiveart.lo/{localization}', $environmentProd->getUrls()[0]->getUrl());
    }

    public function testFindWebspaceByKey(): void
    {
        $webspace = $this->webspaceManager->findWebspaceByKey('sulu_io');
        $this->assertInstanceOf(Webspace::class, $webspace);

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());

        $this->assertEquals(2, \count($webspace->getLocalizations()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(3, \count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[1]->getRedirect());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindPortalByKey(): void
    {
        $portal = $this->webspaceManager->findPortalByKey('sulucmf_at');
        $this->assertInstanceOf(Portal::class, $portal);

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindWebspaceByNotExistingKey(): void
    {
        $portal = $this->webspaceManager->findWebspaceByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalByNotExistingKey(): void
    {
        $portal = $this->webspaceManager->findPortalByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalInformationByUrl(): void
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.at/test/test/test', 'prod');
        $this->assertNotNull($portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());

        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());

        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(3, \count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindPortalInformationsByUrl(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl('sulu.at/test/test/test', 'prod');
        $this->assertNotCount(0, $portalInformations);
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());

        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());

        $webspaceLocalizations = $webspace->getLocalizations();
        $this->assertCount(2, $webspaceLocalizations);
        $this->assertEquals('en', $webspaceLocalizations[0]->getLanguage());
        $this->assertEquals('us', $webspaceLocalizations[0]->getCountry());
        $this->assertEquals('auto', $webspaceLocalizations[0]->getShadow());
        $this->assertEquals('de', $webspaceLocalizations[1]->getLanguage());
        $this->assertEquals('at', $webspaceLocalizations[1]->getCountry());
        $this->assertEquals('', $webspaceLocalizations[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, \count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());

        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());

        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();
        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $portalLocalizations = $portal->getLocalizations();
        $this->assertEquals(2, \count($portalLocalizations));
        $this->assertEquals('en', $portalLocalizations[0]->getLanguage());
        $this->assertEquals('us', $portalLocalizations[0]->getCountry());
        $this->assertEquals('', $portalLocalizations[0]->getShadow());
        $this->assertEquals('de', $portalLocalizations[1]->getLanguage());
        $this->assertEquals('at', $portalLocalizations[1]->getCountry());
        $this->assertEquals('', $portalLocalizations[1]->getShadow());

        $this->assertEquals(3, \count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    /**
     * @return array<array{string, bool}>
     */
    public static function provideFindPortalInformationByUrl()
    {
        return [
            ['dan.lo/de-asd/test/test', false],
            ['dan.lo/de-asd/test/test.rss', false],
            ['dan.lo/de/test/test', true],
            ['dan.lo/de/test/test.rss', true],
            ['dan.lo/de-asd', false],
            ['dan.lo/de-asd.rss', false],
            ['dan.lo/de/s', true],
            ['dan.lo/de/s.rss', true],
            ['dan.lo/de', true],
            ['dan.lo/de.rss', true],
        ];
    }

    /**
     * @param bool $shouldFind
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideFindPortalInformationByUrl')]
    public function testFindPortalInformationByUrlWithInvalidSuffix(string $url, $shouldFind): void
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl($url, 'dev');

        if ($shouldFind) {
            $this->assertNotNull($portalInformation);
        } else {
            $this->assertNull($portalInformation);
        }
    }

    public function testFindPortalInformationsByWebspaceKeyAndLocale(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            'sulu_io',
            'de_at',
            'dev'
        );

        $this->assertCount(1, $portalInformations);
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);

        $this->assertEquals('sulu_io', $portalInformation->getWebspace()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testFindPortalInformationsByPortalKeyAndLocale(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByPortalKeyAndLocale(
            'sulucmf_at',
            'de_at',
            'dev'
        );
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);

        $this->assertEquals('sulucmf_at', $portalInformation->getPortal()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testLoadMultiple(): void
    {
        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            $this->requestStack->reveal(),
            [
                'cache_dir' => $this->getResourceDirectory() . '/cache',
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple',
                'cache_class' => 'WebspaceCollectionCache' . \uniqid(),
            ],
            'test',
            'sulu.io',
            'http',
            $this->structureMetadataFactory->reveal()
        );

        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $this->assertEquals(2, $webspaces->length());

        $webspace = $webspaces->getWebspace('massiveart');
        $this->assertInstanceOf(Webspace::class, $webspace);
        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());

        $webspace = $webspaces->getWebspace('sulu_io');
        $this->assertInstanceOf(Webspace::class, $webspace);
        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
    }

    public function testLoadMissingDefaultTemplate(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $this->structureMetadataFactory->getStructures('page')->willReturn([]);

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            $this->requestStack->reveal(),
            [
                'cache_dir' => $this->getResourceDirectory() . '/cache',
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/missing-default-template',
                'cache_class' => 'WebspaceCollectionCache' . \uniqid(),
            ],
            'prod',
            'sulu.io',
            'http',
            $this->structureMetadataFactory->reveal()
        );

        $webspaces = $this->webspaceManager->getWebspaceCollection();
    }

    public function testLoadExcludedDefaultTemplate(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            $this->requestStack->reveal(),
            [
                'cache_dir' => $this->getResourceDirectory() . '/cache',
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/excluded-default-template',
                'cache_class' => 'WebspaceCollectionCache' . \uniqid(),
            ],
            'prod',
            'sulu.io',
            'http',
            $this->structureMetadataFactory->reveal()
        );

        $webspaces = $this->webspaceManager->getWebspaceCollection();
    }

    public function testRedirectUrl(): void
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('www.sulu.at/test/test', 'prod');
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);

        $this->assertEquals('sulu.at', $portalInformation->getRedirect());
        $this->assertEquals('www.sulu.at', $portalInformation->getUrl());

        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()?->getSystem());

        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());
    }

    public function testLocalizations(): void
    {
        $localizations = $this->webspaceManager->findWebspaceByKey('massiveart')?->getLocalizations();
        $this->assertNotNull($localizations);

        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertEquals('us', $localizations[0]->getCountry());
        $this->assertEquals('auto', $localizations[0]->getShadow());

        $this->assertEquals(1, \count($localizations[0]->getChildren()));
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $localizations[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $localizations[0]->getChildren()[0]->getShadow());
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getParent()->getLanguage());
        $this->assertEquals('us', $localizations[0]->getChildren()[0]->getParent()->getCountry());
        $this->assertEquals('auto', $localizations[0]->getChildren()[0]->getParent()->getShadow());

        $this->assertEquals('fr', $localizations[1]->getLanguage());
        $this->assertEquals('ca', $localizations[1]->getCountry());
        $this->assertEquals(null, $localizations[1]->getShadow());

        $allLocalizations = $this->webspaceManager->findWebspaceByKey('massiveart')?->getAllLocalizations();
        $this->assertNotNull($allLocalizations);
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

    public function testFindUrlsByResourceLocator(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithScheme(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator(
            '/test',
            'dev',
            'en_us',
            'massiveart',
            null,
            'https'
        );

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithSchemeNull(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator(
            '/test',
            'dev',
            'en_us',
            'massiveart',
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, null);
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithSchemeFromRequest(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $request->getScheme()->willReturn('https');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['https://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithWebspaceFromRequest(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('dan_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at');

        $this->assertEquals(['http://dan.lo/de/test'], $result);
    }

    public function testFindUrlsByResourceLocatorRoot(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['http://sulu.lo/'], $result);
    }

    public function testFindUrlsByResourceLocatorRootWithScheme(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'en_us', 'massiveart', null, 'https');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo/'], $result);
    }

    public function testFindUrlsByResourceLocatorWithCustomHttpPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'http');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'http');
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithCustomHttpsPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.lo');
        $request->getPort()->willReturn(4444);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'https');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo:4444/test'], $result);
    }

    public function testFindUrlByResourceLocator(): void
    {
        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.lo/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.at/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io', 'sulu.lo');
        $this->assertEquals('http://sulu.lo/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io', 'other-domain.lo');
        $this->assertEquals('http://sulu.at/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            'main',
            'de_at',
            'sulu_io',
            'sulu.lo',
            'https'
        );
        $this->assertEquals('https://sulu.lo/test', $result);
    }

    public function testFindUrlByResourceLocatorWithWebspaceFromRequest(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('dan_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at');
        $this->assertEquals('http://dan.lo/de/test', $result);
    }

    public function testFindUrlByResourceLocatorWithCustomHttpPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'http');
        $this->assertEquals('http://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'http');
        $this->assertEquals('http://sulu.lo/test', $result);
    }

    public function testFindUrlByResourceLocatorWithCustomHttpsPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.lo');
        $request->getPort()->willReturn(4444);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'https');
        $this->assertEquals('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals('https://sulu.lo:4444/test', $result);
    }

    public function testGetPortals(): void
    {
        $portals = $this->webspaceManager->getPortals();

        $this->assertCount(7, $portals);
        $this->assertEquals('massiveart_us', $portals['massiveart_us']->getKey());
        $this->assertEquals('massiveart_ca', $portals['massiveart_ca']->getKey());
        $this->assertEquals('sulucmf_at', $portals['sulucmf_at']->getKey());
        $this->assertEquals('sulucmf_at_host_replacement', $portals['sulucmf_at_host_replacement']->getKey());
        $this->assertEquals('dancmf_at', $portals['dancmf_at']->getKey());
        $this->assertEquals('sulucmf_singlelanguage_at', $portals['sulucmf_singlelanguage_at']->getKey());
        $this->assertEquals(
            'sulucmf_withoutportallocalizations_at',
            $portals['sulucmf_withoutportallocalizations_at']->getKey()
        );
    }

    public function testGetUrls(): void
    {
        $urls = $this->webspaceManager->getUrls('dev');

        $this->assertCount(9, $urls);
        $this->assertContains('sulu.lo', $urls);
        $this->assertContains('sulu-single-language.lo', $urls);
        $this->assertContains('sulu-without.lo', $urls);
        $this->assertContains('massiveart.lo', $urls);
        $this->assertContains('massiveart.lo/en-us', $urls);
        $this->assertContains('massiveart.lo/en-ca', $urls);
        $this->assertContains('massiveart.lo/fr-ca', $urls);
        $this->assertContains('massiveart.lo/de', $urls);
    }

    public function testGetPortalInformations(): void
    {
        $portalInformations = $this->webspaceManager->getPortalInformations('dev');

        $this->assertCount(9, $portalInformations);
        $this->assertArrayHasKey('sulu.lo', $portalInformations);
        $this->assertArrayHasKey('sulu-single-language.lo', $portalInformations);
        $this->assertArrayHasKey('sulu-without.lo', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-us', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-ca', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/fr-ca', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/de', $portalInformations);
    }

    public function testGetAllLocalizations(): void
    {
        $localizations = $this->webspaceManager->getAllLocalizations();

        $localizations = \array_map(
            function($localization) {
                $localization = $localization->toArray();
                unset($localization['children']);
                unset($localization['localization']);
                unset($localization['shadow']);
                unset($localization['default']);
                unset($localization['xDefault']);

                return $localization;
            },
            $localizations
        );

        // check for duplicates
        $this->assertCount(7, $localizations);

        $this->assertContains(
            [
                'country' => 'us',
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'at',
                'language' => 'de',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'ca',
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'ca',
                'language' => 'fr',
            ],
            $localizations
        );

        $this->assertContains(
            [
                'country' => '',
                'language' => 'de',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => '',
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'uk',
                'language' => 'en',
            ],
            $localizations
        );
    }

    public function testGetAllLocalesByWebspaces(): void
    {
        $webspacesLocales = $this->webspaceManager->getAllLocalesByWebspaces();

        foreach ($webspacesLocales as &$webspaceLocales) {
            $webspaceLocales = \array_map(
                function($webspaceLocale) {
                    $webspaceLocale = $webspaceLocale->toArray();
                    unset($webspaceLocale['children']);
                    unset($webspaceLocale['localization']);
                    unset($webspaceLocale['shadow']);
                    unset($webspaceLocale['default']);
                    unset($webspaceLocale['xDefault']);

                    return $webspaceLocale;
                },
                $webspaceLocales
            );
        }

        $this->assertArrayHasKey('sulu_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['sulu_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['sulu_io']);
        $this->assertEquals(['country' => 'us', 'language' => 'en'], $webspacesLocales['sulu_io']['en_us']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], \reset($webspacesLocales['sulu_io']));

        $this->assertArrayHasKey('massiveart', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('en_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('fr_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('de', $webspacesLocales['massiveart']);
        $this->assertEquals(['country' => 'ca', 'language' => 'fr'], \reset($webspacesLocales['massiveart']));

        $this->assertArrayHasKey('dan_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['dan_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['dan_io']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], \reset($webspacesLocales['dan_io']));
    }

    public function testGetWebspaceCollectionReplaceHost(): void
    {
        $portalInformations = \array_values(
            $this->webspaceManager->getPortalInformationsByWebspaceKey(
                'test',
                'sulu_io_host_replacement'
            )
        );

        $this->assertCount(2, $portalInformations);

        $this->assertEquals('sulu.io/de-at', $portalInformations[0]->getUrl());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[0]->getUrlExpression());
        $this->assertEquals('sulu.io', $portalInformations[1]->getUrl());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[1]->getUrlExpression());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[1]->getRedirect());
    }
}
