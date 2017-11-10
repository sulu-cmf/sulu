<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;

class PortalInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalInformation
     */
    private $portalInformation;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var Portal
     */
    private $portal;

    /**
     * @var Localization
     */
    private $localization;

    public function setUp()
    {
        parent::setUp();
        $this->portalInformation = new PortalInformation(null, null, null, null, null);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->portal = $this->prophesize(Portal::class);
        $this->localization = $this->prophesize(Localization::class);
    }

    public function provideUrl()
    {
        return [
            ['sulu.lo', 'sulu.lo', ''],
            ['sulu.io/', 'sulu.io', '/'],
            ['sulu.com/example', 'sulu.com', '/example'],
        ];
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
        $expected = [
            'type' => 'foo',
            'portal' => 'portal_key',
            'webspace' => 'my_webspace',
            'url' => 'http://example.emp',
            'localization' => ['foo'],
            'redirect' => true,
            'main' => false,
            'priority' => 0,
        ];

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
