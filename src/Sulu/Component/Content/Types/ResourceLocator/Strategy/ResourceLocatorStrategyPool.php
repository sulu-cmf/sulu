<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Manages rlp-strategies.
 */
class ResourceLocatorStrategyPool implements ResourceLocatorStrategyPoolInterface
{
    /**
     * @var array<string, ResourceLocatorStrategyInterface>
     */
    private $strategies;

    /**
     * @param iterable<string, ResourceLocatorStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies, private WebspaceManagerInterface $webspaceManager)
    {
        $this->strategies = [...$strategies];
    }

    public function getStrategy($name)
    {
        if (!\array_key_exists($name, $this->strategies)) {
            throw new ResourceLocatorStrategyNotFoundException($name, \array_keys($this->strategies));
        }

        return $this->strategies[$name];
    }

    public function getStrategyByWebspaceKey($webspaceKey)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        return $this->getStrategy($webspace->getResourceLocatorStrategy());
    }
}
