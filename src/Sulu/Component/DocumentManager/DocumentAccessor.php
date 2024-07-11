<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use ProxyManager\Proxy\LazyLoadingInterface;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class DocumentAccessor
{
    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @param object $document
     */
    public function __construct(private $document)
    {
        $documentClass = \get_class($this->document);

        if ($this->document instanceof LazyLoadingInterface) {
            $documentClass = ClassNameInflector::getUserClassName($documentClass);
        }

        $this->reflection = new \ReflectionClass($documentClass);
    }

    /**
     * @param string $field
     *
     * @throws DocumentManagerException
     */
    public function set($field, $value)
    {
        if (!$this->has($field)) {
            throw new DocumentManagerException(\sprintf(
                'Document "%s" must have property "%s" (it is probably required by a behavior)',
                \get_class($this->document), $field
            ));
        }

        $property = $this->reflection->getProperty($field);
        $property->setAccessible(true);
        $property->setValue($this->document, $value);
    }

    public function get($field)
    {
        $property = $this->reflection->getProperty($field);
        // TODO: Can be cached? Makes a performance diff?
        $property->setAccessible(true);

        return $property->getValue($this->document);
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function has($field)
    {
        return $this->reflection->hasProperty($field);
    }
}
