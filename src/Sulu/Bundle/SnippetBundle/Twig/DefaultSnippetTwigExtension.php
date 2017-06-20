<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * @deprecated
 *
 * Load snippets over the sulu_snippet_load_default is deprecated
 * and will be removed in 2.0 use sulu_snippet_load_by_area instead.
 *
 * Provides default snippets.
 */
class DefaultSnippetTwigExtension extends \Twig_Extension
{
    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SnippetResolverInterface
     */
    private $snippetResolver;

    public function __construct(
        DefaultSnippetManagerInterface $defaultSnippetManager,
        RequestAnalyzerInterface $requestAnalyzer,
        SnippetResolverInterface $snippetResolver
    ) {
        $this->defaultSnippetManager = $defaultSnippetManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->snippetResolver = $snippetResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_snippet_load_default', [$this, 'getDefault']),
        ];
    }

    public function getDefault($snippetType, $webspaceKey = null, $locale = null)
    {
        @trigger_error('Load snippets over the sulu_snippet_load_default is deprecated and will be removed in 2.0 use sulu_snippet_load_by_area instead', E_USER_DEPRECATED);

        if (!$webspaceKey) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }
        if (!$locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        $ids = [
            $this->defaultSnippetManager->loadIdentifier($webspaceKey, $snippetType),
        ];

        // to filter null default snippet
        $ids = array_filter($ids);

        return $this->snippetResolver->resolve($ids, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_snippet.default';
    }
}
