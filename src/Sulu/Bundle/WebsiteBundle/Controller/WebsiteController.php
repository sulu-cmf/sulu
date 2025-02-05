<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancerInterface;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Basic class to render Website from phpcr content.
 */
abstract class WebsiteController extends AbstractController
{
    /**
     * Returns a rendered structure.
     *
     * @param StructureInterface $structure The structure, which has been loaded for rendering
     * @param array $attributes Additional attributes, which will be passed to twig
     * @param bool $preview Defines if the site is rendered in preview mode
     * @param bool $partial Defines if only the content block of the template should be rendered
     *
     * @return Response
     */
    protected function renderStructure(
        StructureInterface $structure,
        $attributes = [],
        $preview = false,
        $partial = false
    ) {
        /** @var Request $request */
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // extract format twig file
        if (!$preview) {
            $requestFormat = $request->getRequestFormat();
        } else {
            $requestFormat = 'html';
        }

        $viewTemplate = $structure->getView() . '.' . $requestFormat . '.twig';

        if (!$this->container->get('twig')->getLoader()->exists($viewTemplate)) {
            throw new NotAcceptableHttpException(\sprintf('Page does not exist in "%s" format.', $requestFormat));
        }

        // get attributes to render template
        $data = $this->getAttributes($attributes, $structure, $preview);

        // if partial render only content block else full page
        if ($partial) {
            $content = $this->renderBlockView(
                $viewTemplate,
                'content',
                $data
            );
        } elseif ($preview) {
            $content = $this->renderPreview(
                $viewTemplate,
                $data
            );
        } else {
            $content = $this->renderView(
                $viewTemplate,
                $data
            );
        }

        $response = new Response($content);

        // we need to set the content type ourselves here
        // else symfony will use the accept header of the client and the page could be cached with false content-type
        // see following symfony issue: https://github.com/symfony/symfony/issues/35694
        $mimeType = $request->getMimeType($requestFormat);

        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        if (!$preview && $this->getCacheTimeLifeEnhancer()) {
            $this->getCacheTimeLifeEnhancer()->enhance($response, $structure);
        }

        return $response;
    }

    /**
     * Generates attributes.
     *
     * @param mixed[] $attributes
     * @param bool $preview
     *
     * @return mixed[]
     */
    protected function getAttributes($attributes, ?StructureInterface $structure = null, $preview = false)
    {
        return $this->container->get('sulu_website.resolver.parameter')->resolve(
            $attributes,
            $this->container->get('sulu_core.webspace.request_analyzer'),
            $structure,
            $preview
        );
    }

    /**
     * Returns rendered part of template specified by block.
     *
     * @param string $view
     * @param string $block
     * @param array<string, mixed> $parameters
     */
    protected function renderBlockView($view, $block, $parameters = []): string
    {
        $twig = $this->container->get('twig');
        $parameters = $twig->mergeGlobals($parameters);

        $template = $twig->load($view);

        $level = \ob_get_level();
        \ob_start();

        try {
            $rendered = $template->renderBlock($block, $parameters);
            \ob_end_clean();

            return $rendered;
        } catch (\Exception $e) {
            while (\ob_get_level() > $level) {
                \ob_end_clean();
            }

            throw $e;
        }
    }

    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;

        return $this->renderView('@SuluWebsite/Preview/preview.html.twig', $parameters);
    }

    protected function getCacheTimeLifeEnhancer(): ?CacheLifetimeEnhancerInterface
    {
        if (!$this->container->has('sulu_http_cache.cache_lifetime.enhancer')) {
            return null;
        }

        /** @var CacheLifetimeEnhancerInterface $cacheLifetimeEnhancer */
        $cacheLifetimeEnhancer = $this->container->get('sulu_http_cache.cache_lifetime.enhancer');

        return $cacheLifetimeEnhancer;
    }

    public static function getSubscribedServices(): array
    {
        $subscribedServices = parent::getSubscribedServices();
        $subscribedServices['sulu_website.resolver.parameter'] = ParameterResolverInterface::class;
        $subscribedServices['sulu_core.webspace.request_analyzer'] = RequestAnalyzerInterface::class;
        $subscribedServices['sulu_http_cache.cache_lifetime.enhancer'] = '?' . CacheLifetimeEnhancerInterface::class;

        return $subscribedServices;
    }
}
