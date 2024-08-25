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

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceTest extends TestCase
{
    public function testToArray(): void
    {
        $metadata = new Metadata([]);

        $expected = [
            'key' => 'foo',
            'name' => 'portal_key',
            'localizations' => [
                [
                    'language' => 'fr',
                    'country' => null,
                    'localization' => 'fr',
                    'default' => null,
                    'xDefault' => null,
                    'children' => [],
                    'shadow' => null,
                ],
            ],
            'security' => [
                'system' => 'sec_sys',
                'permissionCheck' => false,
            ],
            'segments' => [
                [
                    'key' => 'asd',
                    'default' => null,
                    'metadata' => $metadata,
                ],
            ],
            'templates' => [],
            'defaultTemplates' => [],
            'excludedTemplates' => [],
            'portals' => [
                [
                    'key' => 'one',
                    'name' => null,
                    'localizations' => [
                        [
                            'language' => 'fr',
                            'country' => null,
                            'localization' => 'fr',
                            'default' => null,
                            'xDefault' => null,
                            'children' => [],
                            'shadow' => null,
                        ],
                    ],
                ],
            ],
            'theme' => 'test',
            'navigation' => [
                'contexts' => [],
            ],
            'resourceLocator' => [
                'strategy' => 'tree_leaf_edit',
            ],
        ];

        $webspace = new Webspace();
        $webspace->setKey($expected['key']);
        $webspace->setName($expected['name']);
        $webspace->setResourceLocatorStrategy($expected['resourceLocator']['strategy']);
        $webspace->setTheme($expected['theme']);

        $security = new Security();
        $security->setSystem($expected['security']['system']);
        $security->setPermissionCheck($expected['security']['permissionCheck']);
        $webspace->setSecurity($security);

        $portal = new Portal();
        $portal->setKey($expected['portals'][0]['key']);
        $portal->setEnvironments([]);
        $webspace->addPortal($portal);

        $localization = new Localization($expected['localizations'][0]['language']);
        $portal->addLocalization($localization);
        $webspace->addLocalization($localization);

        $segment = new Segment();
        $segment->setKey($expected['segments'][0]['key']);
        $segment->setMetadata($metadata);
        $webspace->addSegment($segment);

        $this->assertEquals($expected, $webspace->toArray());
    }

    public function testFindLocalization(): void
    {
        $webspace = new Webspace();

        $localeDe = new Localization('de');
        $localeDeAt = new Localization('de', 'at');
        $localeDeCh = new Localization('de', 'ch');

        $localeDe->addChild($localeDeAt);
        $localeDe->addChild($localeDeCh);

        $localeEn = new Localization('en');

        $webspace->addLocalization($localeDe);
        $webspace->addLocalization($localeEn);

        $result = $webspace->getLocalization('de');
        $this->assertEquals('de', $result->getLocale());

        $result = $webspace->getLocalization('de_at');
        $this->assertEquals('de_at', $result->getLocale());

        $result = $webspace->getLocalization('de_ch');
        $this->assertEquals('de_ch', $result->getLocale());

        $result = $webspace->getLocalization('en');
        $this->assertEquals('en', $result->getLocale());

        $result = $webspace->getLocalization('en_us');
        $this->assertEquals(null, $result);
    }

    public function testHasDomain(): void
    {
        $webspace = new Webspace();

        $portal = new Portal();
        $webspace->addPortal($portal);

        $environment = new Environment('prod');
        $portal->addEnvironment($environment);

        $url = new Url('sulu.lo');
        $environment->addUrl($url);

        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertFalse($webspace->hasDomain('1.sulu.lo', 'prod'));
    }

    public function testHasDomainWildcard(): void
    {
        $webspace = new Webspace();

        $portal = new Portal();
        $webspace->addPortal($portal);

        $environment = new Environment('prod');
        $portal->addEnvironment($environment);

        $url = new Url('{host}');
        $environment->addUrl($url);

        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($webspace->hasDomain('1.sulu.lo', 'prod'));
    }

    public function testHasDomainWithLocalization(): void
    {
        $webspace = new Webspace();

        $portal = new Portal();
        $webspace->addPortal($portal);

        $environment = new Environment('prod');
        $portal->addEnvironment($environment);

        $url = new Url('sulu.lo', 'prod');
        $url->setLanguage('de');
        $url->setCountry('');
        $environment->addUrl($url);

        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod', 'de'));
        $this->assertFalse($webspace->hasDomain('sulu.lo', 'prod', 'en'));
    }

    public function testHasDomainWithLocalizationWithCountry(): void
    {
        $webspace = new Webspace();

        $portal = new Portal();
        $webspace->addPortal($portal);

        $environment = new Environment('prod');
        $portal->addEnvironment($environment);

        $url = new Url('sulu.lo', 'prod');
        $url->setLanguage('de');
        $url->setCountry('at');
        $environment->addUrl($url);

        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod'));
        $this->assertTrue($webspace->hasDomain('sulu.lo', 'prod', 'de_at'));
        $this->assertFalse($webspace->hasDomain('sulu.lo', 'prod', 'de'));
    }

    public function testHasDomainWithLocationAndCountry(): void
    {
        $webspace = new Webspace();

        $portal = new Portal();
        $webspace->addPortal($portal);

        $environment = new Environment('prod');
        $portal->addEnvironment($environment);

        $urlDe = new Url('sulu.de', 'prod');
        $urlDe->setLanguage('de');
        $urlDe->setCountry('');
        $environment->addUrl($urlDe);

        $urlAt = new Url('sulu.at', 'prod');
        $urlAt->setLanguage('de');
        $urlAt->setCountry('at');
        $environment->addUrl($urlAt);

        $this->assertTrue($webspace->hasDomain('sulu.de', 'prod'));
        $this->assertTrue($webspace->hasDomain('sulu.at', 'prod'));
        $this->assertFalse($webspace->hasDomain('sulu.at', 'prod', 'de'));
        $this->assertFalse($webspace->hasDomain('sulu.de', 'prod', 'de_at'));
        $this->assertTrue($webspace->hasDomain('sulu.de', 'prod', 'de'));
        $this->assertTrue($webspace->hasDomain('sulu.at', 'prod', 'de_at'));
    }

    public function testAddTemplate(): void
    {
        $templates = ['error-404' => 'template404'];
        $webspace = new Webspace();
        $webspace->addTemplate('error-404', 'template404');

        $this->assertEquals('template404.html.twig', $webspace->getTemplate('error-404'));
        $this->assertEquals($templates, $webspace->getTemplates());
        $data = $webspace->toArray();
        $this->assertEquals($templates, $data['templates']);
    }

    public function testAddTemplateDefault(): void
    {
        $templates = ['error-404' => 'template404', 'error' => 'template'];

        $webspace = new Webspace();
        $webspace->addTemplate('error', 'template');
        $webspace->addTemplate('error-404', 'template404');

        $this->assertEquals('template404.html.twig', $webspace->getTemplate('error-404'));
        $this->assertEquals('template.html.twig', $webspace->getTemplate('error'));
        $this->assertEquals($templates, $webspace->getTemplates());
        $data = $webspace->toArray();
        $this->assertEquals($templates, $data['templates']);
    }

    public function testAddDefaultTemplate(): void
    {
        $defaultTemplates = ['page' => 'default', 'homepage' => 'overview'];

        $webspace = new Webspace();
        $webspace->addDefaultTemplate('page', 'default');
        $webspace->addDefaultTemplate('homepage', 'overview');
        $this->assertEquals($defaultTemplates, $webspace->getDefaultTemplates());
        $this->assertEquals($defaultTemplates['page'], $webspace->getDefaultTemplate('page'));
        $this->assertEquals($defaultTemplates['homepage'], $webspace->getDefaultTemplate('homepage'));
        $this->assertNull($webspace->getDefaultTemplate('other-type'));
        $data = $webspace->toArray();
        $this->assertEquals($defaultTemplates, $data['defaultTemplates']);
    }

    public function testGetTemplateFormat(): void
    {
        $templates = ['error' => 'template'];

        $webspace = new Webspace();
        $webspace->addTemplate('error', 'template');

        $this->assertEquals('template.json.twig', $webspace->getTemplate('error', 'json'));
        $this->assertEquals($templates, $webspace->getTemplates());
        $data = $webspace->toArray();
        $this->assertEquals($templates, $data['templates']);
    }

    public function testHasWebsiteSecurityWithoutSecurity(): void
    {
        $webspace = new Webspace();
        $this->assertFalse($webspace->hasWebsiteSecurity());
    }

    public function testHasWebsiteSecurityWithoutSystem(): void
    {
        $webspace = new Webspace();
        $security = new Security();
        $security->setPermissionCheck(true);
        $webspace->setSecurity($security);
        $this->assertFalse($webspace->hasWebsiteSecurity());
    }

    public function testHasWebsiteSecurityWithSystem(): void
    {
        $webspace = new Webspace();
        $security = new Security();
        $security->setSystem('test');
        $security->setPermissionCheck(true);
        $webspace->setSecurity($security);
        $this->assertTrue($webspace->hasWebsiteSecurity());
    }

    public function testHasWebsiteSecurityWithoutPermissionCheck(): void
    {
        $webspace = new Webspace();
        $security = new Security();
        $security->setSystem('test');
        $security->setPermissionCheck(false);
        $webspace->setSecurity($security);
        $this->assertFalse($webspace->hasWebsiteSecurity());
    }
}
