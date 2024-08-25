<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Events\PreRenderEvent;
use Sulu\Bundle\PreviewBundle\Preview\Exception\RouteDefaultsProviderNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TemplateNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\TwigException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\UnexpectedException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceLocalizationNotFoundException;
use Sulu\Bundle\PreviewBundle\Preview\Exception\WebspaceNotFoundException;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Error\Error;

/**
 * Renders preview responses.
 */
class PreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var string
     */
    private $defaultHost;

    public function __construct(
        private RouteDefaultsProviderInterface $routeDefaultsProvider,
        private RequestStack $requestStack,
        private KernelFactoryInterface $kernelFactory,
        private WebspaceManagerInterface $webspaceManager,
        private EventDispatcherInterface $eventDispatcher,
        private array $previewDefaults,
        private string $environment,
        private ?string $targetGroupHeader = null,
    ) {
    }

    public function render(
        $object,
        $id,
        $partial = false,
        $options = []
    ) {
        $webspaceKey = $options['webspaceKey'] ?? null;
        $locale = $options['locale'] ?? null;

        if (!$this->routeDefaultsProvider->supports(\get_class($object))) {
            throw new RouteDefaultsProviderNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            $webspaceKey,
            $locale,
            $this->environment
        );

        /** @var PortalInformation $portalInformation */
        $portalInformation = \reset($portalInformations);

        if (!$portalInformation) {
            $portalInformation = $this->createPortalInformation($object, $id, $webspaceKey, $locale);
        }

        $webspace = $portalInformation->getWebspace();
        $segment = isset($options['segmentKey']) ? $webspace->getSegment($options['segmentKey']) : null;
        $localization = $webspace->getLocalization($locale);

        $query = [];
        $request = [];
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null !== $currentRequest) {
            $query = $currentRequest->query->all();
            $request = $currentRequest->request->all();
        }

        $attributes = $this->routeDefaultsProvider->getByEntity(\get_class($object), $id, $locale, $object);
        $attributes['preview'] = true;
        $attributes['partial'] = $partial;
        $attributes['_sulu'] = new RequestAttributes(
            [
                'webspace' => $webspace,
                'segment' => $segment,
                'locale' => $locale,
                'localization' => $localization,
                'portal' => $portalInformation->getPortal(),
                'portalUrl' => $portalInformation->getUrl(),
                'resourceLocatorPrefix' => $portalInformation->getPrefix(),
                'getParameters' => $query,
                'postParameters' => $request,
                'portalInformation' => $portalInformation,
                'scheme' => $currentRequest->getScheme(),
                'host' => $currentRequest->getHost(),
                'port' => $currentRequest->getPort(),
                'dateTime' => isset($options['dateTime']) ? new \DateTime($options['dateTime']) : new \DateTime(),
            ]
        );

        $attributes['_seo'] = [
            'noIndex' => true,
            'noFollow' => true,
        ];

        // get server parameters
        $server = $this->createServerAttributes($portalInformation, $currentRequest);

        $request = new Request($query, $request, $attributes, [], [], $server);
        $request->setLocale($locale);

        if ($this->targetGroupHeader && isset($options['targetGroupId'])) {
            $request->headers->set($this->targetGroupHeader, $options['targetGroupId']);
        }

        // TODO Remove this event in 2.0 as it is not longer needed to set the correct theme.
        $this->eventDispatcher->dispatch(new PreRenderEvent($attributes['_sulu']), Events::PRE_RENDER);

        try {
            $response = $this->handle($request);
        } catch (Error $e) {
            // dev/test only: display also the file and line which was causing the error
            // for better debugging and faster development
            if (\in_array($this->environment, ['dev', 'test'])) {
                $e->appendMessage(' (' . $e->getFile() . ' line ' . $e->getLine() . ')');
            }

            throw new TwigException($e, $object, $id, $webspace, $locale);
        } catch (\InvalidArgumentException $e) {
            throw new TemplateNotFoundException($e, $object, $id, $webspace, $locale);
        } catch (\Exception $e) {
            throw new UnexpectedException($e, $object, $id, $webspace, $locale);
        }

        return $response->getContent();
    }

    /**
     * Handles given request and returns response.
     *
     * @return Response
     *
     * @throws \Exception
     */
    private function handle(Request $request)
    {
        $kernel = $this->kernelFactory->create($this->environment);

        try {
            return $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);
        } catch (HttpException $e) {
            if ($e->getPrevious()) {
                throw $e->getPrevious();
            }

            throw $e;
        }
    }

    /**
     * Create server attributes.
     *
     * @return array
     */
    private function createServerAttributes(PortalInformation $portalInformation, ?Request $currentRequest = null)
    {
        // get server parameters
        $server = [];
        // FIXME default scheme and port should be configurable.
        $scheme = 'http';
        $port = 80;

        if ($currentRequest) {
            $server = $currentRequest->server->all();
            $scheme = $currentRequest->getScheme();
            $port = $currentRequest->getPort();
        }

        $portalUrl = $scheme . '://' . $portalInformation->getUrl();
        $portalUrlParts = \parse_url($portalUrl);

        $serverName = null;
        $httpHost = null;
        $prefixPath = '';

        if (isset($portalUrlParts['path'])) {
            $prefixPath = $portalUrlParts['path'];
        }

        if (isset($portalUrlParts['host'])) {
            $serverName = $portalUrlParts['host'];
            $httpHost = $serverName;
            if (!\in_array($port, [80, 443])) {
                $httpHost .= ':' . $port;
            }
        }

        $server['SERVER_NAME'] = $serverName;
        $server['SERVER_PORT'] = $port;
        $server['HTTP_HOST'] = $httpHost;
        $server['REQUEST_URI'] = $prefixPath . '/_sulu_preview';
        unset($server['HTTP_X_REQUESTED_WITH']); // subrequest should not be detected as ajax

        return $server;
    }

    /**
     * This creates a new portal information based on the given information. This is necessary because it is possible
     * that a webspace defines a language, which is not used in any portal. For this case we have to define our own
     * fake PortalInformation object.
     *
     * @param object $object
     * @param int $id
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return PortalInformation
     *
     * @throws WebspaceLocalizationNotFoundException
     * @throws WebspaceNotFoundException
     */
    private function createPortalInformation($object, $id, $webspaceKey, $locale)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $domain = $this->requestStack->getCurrentRequest()->getHost();

        if (!$webspace) {
            throw new WebspaceNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $webspace = clone $webspace;
        $localization = $webspace->getLocalization($locale);

        if (!$localization) {
            throw new WebspaceLocalizationNotFoundException($object, $id, $webspaceKey, $locale);
        }

        $localization = clone $localization;
        $localization->setXDefault(true);
        $portal = new Portal();
        $portal->setName($webspace->getName());
        $portal->setKey($webspace->getKey());
        $portal->setWebspace($webspace);
        $portal->setXDefaultLocalization($localization);
        $portal->setLocalizations([$localization]);
        $portal->setDefaultLocalization($localization);

        $environment = new Environment('test');
        $environment->addUrl(new Url($domain));

        $portal->addEnvironment($environment);
        $webspace->setPortals([$portal]);

        return new PortalInformation(RequestAnalyzer::MATCH_TYPE_FULL, $webspace, $portal, $localization, $domain);
    }
}
