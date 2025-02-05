<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Manager;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceCollectionTest extends TestCase
{
    private WebspaceCollection $webspaceCollection;

    public function setUp(): void
    {
        $portals = [];
        $portalInformations = ['prod' => [], 'dev' => []];

        // first portal
        $portal = new Portal();
        $portal->setName('Portal1');
        $portal->setKey('portal1');

        $environment = new Environment();
        $environment->setType('prod');
        $url = new Url();
        $url->setUrl('www.portal1.com');
        $url->setLanguage('en');
        $url->setCountry('us');
        $environment->addUrl($url);
        $url = new Url();
        $url->setUrl('portal1.com');
        $url->setRedirect('www.portal1.com');
        $environment->addUrl($url);
        $portal->addEnvironment($environment);

        $localizationEnUs = new Localization('en', 'us');
        $localizationEnUs->setShadow('auto');
        $localizationEnUs->setDefault(true);
        $localizationEnCa = new Localization('en', 'ca');
        $localizationEnCa->setDefault(false);
        $localizationEnUs->addChild($localizationEnCa);
        $localizationFrCa = new Localization('fr', 'ca');
        $localizationFrCa->setDefault(false);

        $portal->addLocalization($localizationEnUs);
        $portal->addLocalization($localizationEnCa);
        $portal->addLocalization($localizationFrCa);
        $portal->setDefaultLocalization($localizationEnUs);

        $webspace = new Webspace();
        $webspace->setKey('default');
        $webspace->addLocalization($localizationEnUs);
        $webspace->addLocalization($localizationFrCa);
        $segmentSummer = new Segment();
        $segmentSummer->setMetadata(['title' => ['en' => 'Summer', 'de' => 'Sommer']]);
        $segmentSummer->setKey('s');
        $segmentSummer->setDefault(true);
        $segmentWinter = new Segment();
        $segmentWinter->setMetadata(['title' => ['en' => 'Winter', 'de' => 'Winter']]);
        $segmentWinter->setKey('w');
        $segmentWinter->setDefault(false);
        $webspace->addSegment($segmentSummer);
        $webspace->addSegment($segmentWinter);
        $webspace->setTheme('portal1theme');
        $webspace->addPortal($portal);
        $webspace->setName('Default');
        $webspace->setResourceLocatorStrategy('tree_leaf_edit');
        $webspace->addPortal($portal);

        $webspace->setNavigation(new Navigation([new NavigationContext('main', [])]));

        $portalInformations['prod']['www.portal1.com'] = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localizationEnUs,
            'www.portal1.com',
        );

        $portalInformations['dev']['portal1.lo'] = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localizationEnUs,
            'portal1.lo',
        );

        $this->webspaceCollection = new WebspaceCollection(['default' => $webspace]);
        $this->webspaceCollection->setPortals(['portal1' => $portal]);
        $this->webspaceCollection->setPortalInformations($portalInformations);
    }

    public function testGetPortalInformations(): void
    {
        $this->assertCount(1, $this->webspaceCollection->getPortalInformations('dev'));
        $this->assertCount(
            1,
            $this->webspaceCollection->getPortalInformations('dev', [RequestAnalyzerInterface::MATCH_TYPE_FULL])
        );
        $this->assertCount(
            0,
            $this->webspaceCollection->getPortalInformations('dev', [RequestAnalyzerInterface::MATCH_TYPE_REDIRECT])
        );
    }

    public function testToArray(): void
    {
        $collectionArray = $this->webspaceCollection->toArray();

        $webspace = $collectionArray['webspaces'][0];

        $this->assertEquals('Default', $webspace['name']);
        $this->assertEquals('default', $webspace['key']);
        $this->assertEquals('us', $webspace['localizations'][0]['country']);
        $this->assertEquals('en', $webspace['localizations'][0]['language']);
        $this->assertEquals(true, $webspace['localizations'][0]['default']);
        $this->assertEquals('ca', $webspace['localizations'][0]['children'][0]['country']);
        $this->assertEquals('en', $webspace['localizations'][0]['children'][0]['language']);
        $this->assertEquals(false, $webspace['localizations'][0]['children'][0]['default']);
        $this->assertEquals('ca', $webspace['localizations'][1]['country']);
        $this->assertEquals('fr', $webspace['localizations'][1]['language']);
        $this->assertEquals(false, $webspace['localizations'][1]['default']);
        $this->assertEquals('Summer', $webspace['segments'][0]['metadata']['title']['en']);
        $this->assertEquals('Sommer', $webspace['segments'][0]['metadata']['title']['de']);
        $this->assertEquals('s', $webspace['segments'][0]['key']);
        $this->assertEquals(true, $webspace['segments'][0]['default']);
        $this->assertEquals('Winter', $webspace['segments'][1]['metadata']['title']['en']);
        $this->assertEquals('Winter', $webspace['segments'][1]['metadata']['title']['de']);
        $this->assertEquals('w', $webspace['segments'][1]['key']);
        $this->assertEquals(false, $webspace['segments'][1]['default']);
        $this->assertEquals('portal1theme', $webspace['theme']);

        $this->assertEquals(1, \count($webspace['navigation']));
        $this->assertEquals(1, \count($webspace['navigation']['contexts']));
        $this->assertEquals('main', $webspace['navigation']['contexts'][0]['key']);
        $this->assertEquals([], $webspace['navigation']['contexts'][0]['metadata']);
        $this->assertEquals('tree_leaf_edit', $webspace['resourceLocator']['strategy']);

        $portal = $webspace['portals'][0];

        $this->assertEquals('Portal1', $portal['name']);
        $this->assertEquals('portal1', $portal['key']);
        $this->assertEquals('prod', $portal['environments'][0]['type']);
        $this->assertEquals('www.portal1.com', $portal['environments'][0]['urls'][0]['url']);
        $this->assertEquals('en', $portal['environments'][0]['urls'][0]['language']);
        $this->assertEquals('us', $portal['environments'][0]['urls'][0]['country']);
        $this->assertEquals(null, $portal['environments'][0]['urls'][0]['segment']);
        $this->assertEquals(null, $portal['environments'][0]['urls'][0]['redirect']);
        $this->assertEquals('portal1.com', $portal['environments'][0]['urls'][1]['url']);
        $this->assertEquals('www.portal1.com', $portal['environments'][0]['urls'][1]['redirect']);
        $this->assertEquals('us', $portal['localizations'][0]['country']);
        $this->assertEquals('en', $portal['localizations'][0]['language']);
        $this->assertEquals(true, $portal['localizations'][0]['default']);
        $this->assertEquals('ca', $portal['localizations'][1]['country']);
        $this->assertEquals('en', $portal['localizations'][1]['language']);
        $this->assertEquals(false, $portal['localizations'][1]['default']);
        $this->assertEquals('ca', $portal['localizations'][2]['country']);
        $this->assertEquals('fr', $portal['localizations'][2]['language']);
        $this->assertEquals(false, $portal['localizations'][2]['default']);

        $portalInformation = $collectionArray['portalInformations']['prod']['www.portal1.com'];

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformation['type']);
        $this->assertEquals('default', $portalInformation['webspace']);
        $this->assertEquals('portal1', $portalInformation['portal']);
        $this->assertEquals('en_us', $portalInformation['localization']['localization']);
        $this->assertEquals('www.portal1.com', $portalInformation['url']);

        $portalInformation = $collectionArray['portalInformations']['dev']['portal1.lo'];

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformation['type']);
        $this->assertEquals('default', $portalInformation['webspace']);
        $this->assertEquals('portal1', $portalInformation['portal']);
        $this->assertEquals('en_us', $portalInformation['localization']['localization']);
        $this->assertEquals('portal1.lo', $portalInformation['url']);
    }

    public function testGetPortalInformationsUnknown(): void
    {
        $this->expectExceptionMessage('Unknown portal environment "unknown"');
        $this->expectException(\InvalidArgumentException::class);
        $this->webspaceCollection->getPortalInformations('unknown');
    }
}
