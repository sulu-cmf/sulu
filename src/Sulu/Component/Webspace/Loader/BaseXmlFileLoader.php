<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * This is the base class for webspace xml file loaders. It offers an supports method, which will not only check if the
 * file is in the xml format, but also if the loader supports the given schema. The supported schema has to be
 * overriden by the base classby defining its own SCHEMA_URI constant.
 */
abstract class BaseXmlFileLoader extends FileLoader
{
    public const SCHEMA_IDENTIFIER = 'http://schemas.sulu.io/webspace/webspace';

    public const SCHEMA_URI = '';

    public function supports($resource, $type = null): bool
    {
        if (!\is_string($resource) || 'xml' !== \pathinfo($resource, \PATHINFO_EXTENSION)) {
            return false;
        }

        $document = XmlUtils::loadFile($resource);
        $namespaces = $document->documentElement->attributes->getNamedItem('schemaLocation')->nodeValue;

        $start = \strpos($namespaces, static::SCHEMA_IDENTIFIER) + \strlen(static::SCHEMA_IDENTIFIER) + 1;
        $namespace = \substr($namespaces, $start);

        $end = \strpos($namespace, ' ');
        if (false !== $end) {
            $namespace = \substr($namespace, 0, $end);
        }

        return $namespace === static::SCHEMA_URI;
    }
}
