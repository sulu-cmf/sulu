<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Extension;

/**
 * Container for extension data.
 *
 * See documentation for ManagedExtensionContainer.
 */
class ExtensionContainer implements \ArrayAccess, \Iterator
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Return an array representation of the data.
     *
     * If this is an instance of ManagedExtensionContainer, then
     * the data will represent the processed extension data.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($extensionName)
    {
        if (isset($this->data[$extensionName])) {
            return $this->data[$extensionName];
        }

        return;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($extensionName, $data): void
    {
        $this->data[$extensionName] = $data;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($extensionName): void
    {
        unset($this->data[$extensionName]);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return \current($this->data);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return \key($this->data);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        return \next($this->data);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        return \reset($this->data);
    }

    #[\ReturnTypeWillChange]
    public function valid(): bool
    {
        return null !== isset($this->data);
    }
}
