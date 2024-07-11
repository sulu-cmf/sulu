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

use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to handle snippets.
 */
class SnippetTwigExtension extends AbstractExtension implements SnippetTwigExtensionInterface
{
    public function __construct(
        private ContentMapperInterface $contentMapper,
        private RequestAnalyzerInterface $requestAnalyzer,
        private StructureResolverInterface $structureResolver,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_snippet_load', [$this, 'loadSnippet']),
        ];
    }

    public function loadSnippet($uuid, $locale = null, $loadExcerpt = false)
    {
        if (null === $locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        try {
            $snippet = $this->contentMapper->load($uuid, $this->requestAnalyzer->getWebspace()->getKey(), $locale);

            return $this->structureResolver->resolve($snippet, $loadExcerpt);
        } catch (DocumentNotFoundException $ex) {
            return;
        }
    }
}
