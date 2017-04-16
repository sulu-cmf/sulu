<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatCache;

use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyUrlNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Route;

/**
 * @package Sulu\Bundle\MediaBundle\Media\FormatCache
 */
class LocalFormatCache implements FormatCacheInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $pathUrl;

    /**
     * @var int
     */
    protected $segments;

    /**
     * @var array
     */
    protected $formats;

    public function __construct(Filesystem $filesystem, $path, $pathUrl, $segments, $formats)
    {
        /**
         * @var Route $route
         */
        $this->filesystem = $filesystem;
        $this->path = $path;
        $this->pathUrl = $pathUrl;
        $this->segments = intval($segments);
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function save($tmpPath, $id, $fileName, $options, $format)
    {
        $savePath = $this->getPath($this->path, $id, $fileName, $format);
        if (!is_dir(dirname($savePath))) {
            $this->filesystem->mkdir(dirname($savePath), 0775);
        }

        try {
            $this->filesystem->copy($tmpPath, $savePath);
        } catch (IOException $ioException) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($id, $fileName, $options)
    {
        foreach ($this->formats as $format) {
            $path = $this->getPath($this->path, $id, $fileName, $format['name']);
            $this->filesystem->remove($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaUrl($id, $fileName, $options, $format)
    {
        return $this->getPathUrl($this->pathUrl, $id, $fileName, $format);
    }

    /**
     * @param string $prePath
     * @param int $id
     * @param string $fileName
     * @param string $format
     * @return string
     */
    protected function getPath($prePath, $id, $fileName, $format)
    {
        $segment = ($id % $this->segments) . '/';
        $prePath = rtrim($prePath, '/');

        return $prePath . '/' . $format . '/' . $segment . $id . '-' . $fileName;
    }

    /**
     * @param string $prePath
     * @param int $id
     * @param string $fileName
     * @param string $format
     * @return string
     */
    protected function getPathUrl($prePath, $id, $fileName, $format)
    {
        $segment = ($id % $this->segments) . '/';
        $prePath = rtrim($prePath, '/');

        return str_replace('{slug}', $format . '/' . $segment . $id . '-' . $fileName, $prePath);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzedMediaUrl($url)
    {
        if (empty($url)) {
            throw new ImageProxyUrlNotFoundException('The given url was empty');
        }

        $id = $this->getIdFromUrl($url);
        $format = $this->getFormatFromUrl($url);

        return array($id, $format);
    }

    /**
     * return the id of by a given url
     * @param string $url
     * @return int
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl
     */
    protected function getIdFromUrl($url)
    {
        $fileName = basename($url);
        $idParts = explode('-', $fileName);

        if (count($idParts) < 2) {
            throw new ImageProxyInvalidUrl('No `id` was found in the url');
        }

        $id = $idParts[0];

        if (preg_match('/[^0-9]/', $id)) {
            throw new ImageProxyInvalidUrl('The founded `id` was not a valid integer');
        }

        return $id;
    }

    /**
     * return the format by a given url
     * @param string $url
     * @return string
     * @throws \Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyInvalidUrl
     */
    protected function getFormatFromUrl($url)
    {
        $path = dirname($url);

        $formatParts = array_reverse(explode('/', $path));

        if (count($formatParts) < 2) {
            throw new ImageProxyInvalidUrl('No `format` was found in the url');
        }

        $format = $formatParts[1];

        return $format;
    }
} 
