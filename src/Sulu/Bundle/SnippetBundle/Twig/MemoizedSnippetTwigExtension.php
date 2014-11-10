<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Component\Cache\MemoizeInterface;

/**
 * Provides memoized Twig functions to handle snippets
 */
class MemoizedSnippetTwigExtension extends \Twig_Extension implements SnippetTwigExtensionInterface
{
    /**
     * @var SnippetTwigExtensionInterface
     */
    private $extension;

    /**
     * @var MemoizeInterface
     */
    private $memoize;

    /**
     * @var int
     */
    private $lifeTime;

    /**
     * Constructor
     */
    function __construct(SnippetTwigExtensionInterface $extension, MemoizeInterface $memoize, $lifeTime)
    {
        $this->extension = $extension;
        $this->memoize = $memoize;
        $this->lifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->extension->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->extension->getFunctions();
    }

    /**
     * {@inheritdoc}
     */
    public function loadSnippet($uuid, $locale = null)
    {
        return $this->memoize->memoize(array($this->extension, 'loadSnippet'), $this->lifeTime);
    }
}
