<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides snippets by area.
 */
class SnippetAreaTwigExtension extends AbstractExtension
{
    public function __construct(
        private DefaultSnippetManagerInterface $defaultSnippetManager,
        private RequestAnalyzerInterface $requestAnalyzer,
        private SnippetResolverInterface $snippetResolver,
        private ReferenceStoreInterface $snippetAreaReferenceStore,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_snippet_load_by_area', [$this, 'loadByArea']),
        ];
    }

    /**
     * Load snippet for webspace by area.
     *
     * @param string $area
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return array
     */
    public function loadByArea($area, $webspaceKey = null, $locale = null)
    {
        if (!$webspaceKey) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }
        if (!$locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        $this->snippetAreaReferenceStore->add($area);

        try {
            $snippet = $this->defaultSnippetManager->load($webspaceKey, $area, $locale);
        } catch (WrongSnippetTypeException $exception) {
            return null;
        } catch (DocumentNotFoundException $exception) {
            return null;
        }

        if (!$snippet) {
            return null;
        }

        $snippets = $this->snippetResolver->resolve([$snippet->getUuid()], $webspaceKey, $locale);

        if (!\array_key_exists(0, $snippets)) {
            return null;
        }

        return $snippets[0];
    }
}
