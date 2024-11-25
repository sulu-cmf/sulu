<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\UserInterface\Controller\Website;

use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * TODO this controller will later replace the DefaultController of the WebsiteBundle
 *      and should be base for all content controllers.
 *
 * @template T of DimensionContentInterface
 */
class ContentController extends AbstractController
{
    /**
     * @param T $object
     */
    public function indexAction(
        Request $request,
        DimensionContentInterface $object,
        string $view, // TODO maybe inject metadata where we also get the cachelifetime from
        bool $preview = false,
        bool $partial = false,
    ): Response {
        $requestFormat = $request->getRequestFormat() ?? 'html';

        $parameters = $this->resolveSuluParameters($object, 'json' === $requestFormat);

        if ('json' === $requestFormat) {
            $response = new JsonResponse($parameters);
        } else {
            $response = new Response($this->renderSuluView($view, $requestFormat, $parameters, $preview, $partial));
        }

        $response->setPublic();

        // TODO resolve cachelifetime
        // TODO implement cacheLifetimeRequestStore

        return $response;
    }

    /**
     * @param T $object
     *
     * @return array<string, mixed>
     */
    protected function resolveSuluParameters(DimensionContentInterface $object, bool $normalize): array
    {
        if (false === $normalize) {
            return $this->container->get('sulu_content.content_resolver')->resolve($object);
        }

        return []; // TODO normalize JSON response
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws NotAcceptableHttpException
     */
    protected function renderSuluView(
        string $view,
        string $requestFormat, // TODO maybe we should avoid this and resolve it before
        array $parameters,
        bool $preview,
        bool $partial,
    ): string {
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        if (!$this->container->get('twig')->getLoader()->exists($viewTemplate)) {
            throw new NotAcceptableHttpException(\sprintf('Page does not exist in "%s" format.', $requestFormat));
        }

        if ($partial) {
            return $this->renderBlockView($viewTemplate, 'content', $parameters);
        } elseif ($preview) {
            $parameters['previewParentTemplate'] = $viewTemplate;
            $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;
            $viewTemplate = '@SuluWebsite/Preview/preview.html.twig';
        }

        return $this->renderView($viewTemplate, $parameters);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['sulu_content.content_resolver'] = ContentResolverInterface::class;

        return $services;
    }
}
