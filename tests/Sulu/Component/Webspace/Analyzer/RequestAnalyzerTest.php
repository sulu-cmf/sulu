<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;


use PHPUnit_Framework_MockObject_MockObject;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Webspace\WebspaceContext;

class RequestAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzer
     */
    private $requestAnalyzer;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $webspaceManager;

    public function setUp()
    {
        $this->webspaceManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Webspace\Manager\WebspaceManagerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('findPortalInformationByUrl')
        );

        $webspaceContext = new WebspaceContext();
        $this->requestAnalyzer = new RequestAnalyzer($this->webspaceManager, 'prod', $webspaceContext);
    }

    /**
     * @param $portalInformation
     */
    protected function prepareWebspaceManager($portalInformation)
    {
        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue($portalInformation)
        );
    }

    public function provideAnalyze()
    {
        return array(
            array(
                array(
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ),
                array(
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                ),
            ),
            array(
                array(
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ),
                array(
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                ),
            ),
        );
    }

    public function provideAnalyzeWithFormat()
    {
        return array(
            array(
                array(
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to.html',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ),
                array(
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'html'
                ),
            ),
            array(
                array(
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to.rss',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ),
                array(
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                    'format' => 'rss'
                ),
            ),
            array(
                array(
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ),
                array(
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => null
                ),
            ),
        );
    }

    /**
     * @dataProvider provideAnalyze
     */
    public function testAnalyze($config, $expected = array())
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            null,
            $config['redirect']
        );

        $this->prepareWebspaceManager($portalInformation);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(array('post' => 1));
        $request->query = new ParameterBag(array('get' => 1));
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue($config['path_info']));
        $request->expects($this->once())->method('setLocale')->with('de_at');
        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals($expected['portal_url'], $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals($expected['redirect'], $this->requestAnalyzer->getCurrentRedirect());
        $this->assertEquals($expected['resource_locator'], $this->requestAnalyzer->getCurrentResourceLocator());
        $this->assertEquals($expected['resource_locator_prefix'], $this->requestAnalyzer->getCurrentResourceLocatorPrefix());
        $this->assertEquals(array('post' => 1), $this->requestAnalyzer->getCurrentPostParameter());
        $this->assertEquals(array('get' => 1), $this->requestAnalyzer->getCurrentGetParameter());
    }

    /**
     * @dataProvider provideAnalyzeWithFormat
     */
    public function testAnalyzeWithFormat($config, $expected = array())
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            null,
            $config['redirect']
        );

        $this->prepareWebspaceManager($portalInformation);

        $requestFormat = false;

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(array('post' => 1));
        $request->query = new ParameterBag(array('get' => 1));
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue($config['path_info']));
        $request->expects($this->once())->method('setLocale')->with('de_at');

        if ($expected['format']) {
            $request->expects($this->once())->method('setRequestFormat')->will(
                $this->returnCallback(
                    function ($format) use (&$requestFormat) {
                        $requestFormat = $format;
                    }
                )
            );
        }

        $request->expects($this->once())->method('getRequestFormat')->will(
            $this->returnCallback(
                function () use (&$requestFormat) {
                    return $requestFormat;
                }
            )
        );

        $this->requestAnalyzer->analyze($request);

        $this->assertEquals('de_at', $this->requestAnalyzer->getCurrentLocalization()->getLocalization());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentWebspace()->getKey());
        $this->assertEquals('sulu', $this->requestAnalyzer->getCurrentPortal()->getKey());
        $this->assertEquals(null, $this->requestAnalyzer->getCurrentSegment());
        $this->assertEquals($expected['portal_url'], $this->requestAnalyzer->getCurrentPortalUrl());
        $this->assertEquals($expected['redirect'], $this->requestAnalyzer->getCurrentRedirect());
        $this->assertEquals($expected['resource_locator'], $this->requestAnalyzer->getCurrentResourceLocator());
        $this->assertEquals(
            $expected['resource_locator_prefix'],
            $this->requestAnalyzer->getCurrentResourceLocatorPrefix()
        );
        $this->assertEquals($expected['format'], $request->getRequestFormat());
        $this->assertEquals(array('post' => 1), $this->requestAnalyzer->getCurrentPostParameter());
        $this->assertEquals(array('get' => 1), $this->requestAnalyzer->getCurrentGetParameter());
    }

    /**
     * @expectedException \Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException
     */
    public function testAnalyzeNotExisting()
    {
        $this->webspaceManager->expects($this->any())->method('findPortalInformationByUrl')->will(
            $this->returnValue(null)
        );

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(array('post' => 1));
        $request->query = new ParameterBag(array('get' => 1));
        
        $this->requestAnalyzer->analyze($request);
    }
}
