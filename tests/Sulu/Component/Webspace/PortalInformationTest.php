<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;


class PortalInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalInformation
     */
    private $portalInformation;

    public function setUp()
    {
        parent::setUp();
        $this->portalInformation = new PortalInformation(null, null, null, null, null);
        $this->webspace = $this->prophesize('Sulu\Component\Webspace\Webspace');
        $this->portal = $this->prophesize('Sulu\Component\Webspace\Portal');
        $this->localization = $this->prophesize('Sulu\Component\Localization\Localization');
    }

    public function provideUrl()
    {
        return array(
            array('sulu.lo', 'sulu.lo', ''),
            array('sulu.io/', 'sulu.io', '/'),
            array('sulu.com/example', 'sulu.com', '/example')
        );
    }

    /**
     * @dataProvider provideUrl
     */
    public function testGetHostAndPrefix($url, $host, $prefix)
    {
        $this->portalInformation->setUrl($url);

        $this->assertEquals($host, $this->portalInformation->getHost());
        $this->assertEquals($prefix, $this->portalInformation->getPrefix());
    }

    public function testToArray()
    {
        $expected = array(
            'type' => 'foo',
            'portal' => 'portal_key',
            'webspace' => 'my_webspace',
            'url' => 'http://example.emp',
            'portal' => 'portal',
            'localization' => array('foo'),
            'redirect' => true,
        );

        $this->portal->getKey()->willReturn($expected['portal']);
        $this->webspace->getKey()->willReturn($expected['webspace']);
        $this->localization->toArray()->willReturn($expected['localization']);

        $this->portalInformation->setType($expected['type']);
        $this->portalInformation->setUrl($expected['url']);
        $this->portalInformation->setWebspace($this->webspace->reveal());
        $this->portalInformation->setPortal($this->portal->reveal());
        $this->portalInformation->setLocalization($this->localization->reveal());
        $this->portalInformation->setRedirect($expected['redirect']);

        $res = $this->portalInformation->toArray();
        $this->assertEquals($expected, $res);
    }
}
