<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit;

use Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache;
use Symfony\Component\Filesystem\Filesystem;

class LocalFormatCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalFormatCache
     */
    protected $localStorage;

    /**
     * @var int
     */
    protected $segments = 10;

    /**
     * setUp.
     */
    public function setUp()
    {
        $fileSystem = new Filesystem();
        $this->localStorage = new LocalFormatCache(
            $fileSystem,
            '/web/uploads/media',
            '/uploads/media/{slug}',
            $this->segments,
            array(
                '50x50' => array(
                    'name' => '50x50',
                ),
            )
        );
    }

    /**
     * testMediaUrlEncoding.
     */
    public function testMediaUrlEncoding()
    {
        $version = 2;
        $fileId = 1;
        $segment = ($fileId % $this->segments);
        $format = '50x50';
        $fileName = 'Test With Spaces & Co.jpg';
        $filePath = $this->localStorage->getMediaUrl($fileId, $fileName, array(), $format, $version);
        $encodedFileName = 'Test%20With%20Spaces%20%26%20Co.jpg';

        $this->assertSame(
            '/uploads/media/50x50/01/1-Test%20With%20Spaces%20%26%20Co.jpg?v=2',
            $filePath
        );
    }
}
