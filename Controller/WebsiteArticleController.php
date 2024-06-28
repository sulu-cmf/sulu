<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Controller;

use Sulu\Bundle\ArticleBundle\Document\ArticleDocument;
use Sulu\Bundle\ArticleBundle\Document\ArticleInterface;
use Sulu\Bundle\ArticleBundle\Document\ArticlePageDocument;
use Sulu\Bundle\ArticleBundle\Resolver\ArticleContentResolverInterface;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Twig\Environment;

/**
 * Handles articles.
 */
class WebsiteArticleController extends AbstractController
{
    /**
     * Article index action.
     */
    public function indexAction(
        Request $request,
        ArticleInterface $object,
        string $view,
        int $pageNumber = 1,
        bool $preview = false,
        bool $partial = false
    ): Response {
        return $this->renderArticle($request, $object, $view, $pageNumber, $preview, $partial);
    }

    /**
     * Render article with given view.
     */
    protected function renderArticle(
        Request $request,
        ArticleInterface $object,
        string $view,
        int $pageNumber,
        bool $preview,
        bool $partial,
        array $attributes = []
    ): Response {
        $object = $this->normalizeArticle($object);

        $requestFormat = $request->getRequestFormat();
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        if (!$this->container->get('twig')->getLoader()->exists($viewTemplate)) {
            throw new NotAcceptableHttpException(\sprintf('Page does not exist in "%s" format.', $requestFormat));
        }

        $content = $this->resolveArticle($object, $pageNumber);

        $parameters = $this->container->get('sulu_website.resolver.parameter')->resolve(
            [],
            $this->container->get('sulu_core.webspace.request_analyzer'),
            null,
            $preview
        );
        $data = \array_merge($parameters, $content, $attributes);

        try {
            if ($partial) {
                $response = $this->createResponse($request);
                $response->setContent(
                    $this->renderBlockView(
                        $viewTemplate,
                        'content',
                        $data
                    )
                );

                return $response;
            } elseif ($preview) {
                $parameters = [
                    'previewParentTemplate' => $viewTemplate,
                    'previewContentReplacer' => Preview::CONTENT_REPLACER,
                ];

                return $this->render(
                    '@SuluWebsite/Preview/preview.html.twig',
                    \array_merge($data, $parameters),
                    $this->createResponse($request)
                );
            } else {
                return $this->render(
                    $viewTemplate,
                    $data,
                    $this->createResponse($request)
                );
            }
        } catch (\InvalidArgumentException $exception) {
            // template not found
            throw new HttpException(406, 'Error encountered when rendering content', $exception);
        }
    }

    /**
     * Returns all the times the article-document.
     * This is necessary because the preview system passes an article-page here.
     */
    protected function normalizeArticle(ArticleInterface $object): ArticleDocument
    {
        if ($object instanceof ArticlePageDocument) {
            return $object->getParent();
        }

        return $object;
    }

    /**
     * Serialize given article with page-number.
     */
    protected function resolveArticle(ArticleInterface $object, int $pageNumber): array
    {
        $articleContentResolver = $this->getArticleContentResolver();

        return $articleContentResolver->resolve($object, $pageNumber);
    }

    /**
     * Create response.
     */
    private function createResponse(Request $request): Response
    {
        $response = new Response();
        $cacheLifetime = $request->attributes->get('_cacheLifetime');

        if ($cacheLifetime) {
            $response->setPublic();
            $response->headers->set(
                SuluHttpCache::HEADER_REVERSE_PROXY_TTL,
                $cacheLifetime
            );
            $response->setMaxAge($this->getParameter('sulu_http_cache.cache.max_age'));
            $response->setSharedMaxAge($this->getParameter('sulu_http_cache.cache.shared_max_age'));
        }

        // we need to set the content type ourselves here
        // else symfony will use the accept header of the client and the page could be cached with false content-type
        // see following symfony issue: https://github.com/symfony/symfony/issues/35694
        $mimeType = $request->getMimeType($request->getRequestFormat());

        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function renderBlockView(string $view, string $block, array $parameters = []): string
    {
        $twig = $this->getTwig();

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

    protected function getTwig(): Environment
    {
        return $this->container->get('twig');
    }

    /**
     * @deprecated
     */
    protected function getTemplateAttributeResolver(): TemplateAttributeResolverInterface
    {
        @\trigger_error(__METHOD__ . '() is deprecated since version 2.2 and will be removed in 3.0. If you need the service, you can inject it by yourself instead.', \E_USER_DEPRECATED);

        return $this->container->get('sulu_website.resolver.template_attribute');
    }

    protected function getArticleContentResolver(): ArticleContentResolverInterface
    {
        return $this->container->get('sulu_article.article_content_resolver');
    }

    public static function getSubscribedServices(): array
    {
        $subscribedServices = parent::getSubscribedServices();

        $subscribedServices['twig'] = Environment::class;
        $subscribedServices['sulu_website.resolver.template_attribute'] = TemplateAttributeResolverInterface::class;
        $subscribedServices['sulu_article.article_content_resolver'] = ArticleContentResolverInterface::class;
        $subscribedServices['sulu_website.resolver.parameter'] = ParameterResolverInterface::class;
        $subscribedServices['sulu_core.webspace.request_analyzer'] = RequestAnalyzerInterface::class;

        return $subscribedServices;
    }
}
