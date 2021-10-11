<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\UserInterface\Controller;

use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class PublicPreviewController
{
    use RequestParametersTrait;

    /**
     * @var PreviewRendererInterface
     */
    private $previewRenderer;

    /**
     * @var PreviewObjectProviderRegistryInterface
     */
    private $previewObjectProviderRegistry;

    /**
     * @var PreviewLinkRepositoryInterface
     */
    private $previewLinkRepository;

    /**
     * @var Profiler|null
     */
    private $profiler;

    public function __construct(
        PreviewRendererInterface $previewRenderer,
        PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry,
        PreviewLinkRepositoryInterface $previewLinkRepository,
        Profiler $profiler = null
    ) {
        $this->previewRenderer = $previewRenderer;
        $this->previewObjectProviderRegistry = $previewObjectProviderRegistry;
        $this->previewLinkRepository = $previewLinkRepository;
        $this->profiler = $profiler;
    }

    public function renderAction(string $token): Response
    {
        $previewLink = $this->previewLinkRepository->findByToken($token);
        if (!$previewLink) {
            return new Response(null, 404);
        }

        $previewLink->increaseVisitCount();
        $this->previewLinkRepository->commit();

        $resourceKey = $previewLink->getResourceKey();
        $resourceId = $previewLink->getResourceId();
        $locale = $previewLink->getLocale();
        $options = $previewLink->getOptions();
        $options['locale'] = $locale;

        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($resourceKey);
        $object = $provider->getObject($resourceId, $locale);

        $content = $this->previewRenderer->render($object, $resourceId, false, $options);

        $this->disableProfiler();

        return new Response($content);
    }

    private function disableProfiler(): void
    {
        if (!$this->profiler) {
            return;
        }

        $this->profiler->disable();
    }
}
