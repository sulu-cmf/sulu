<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default request analyzer will be used for sulu-admin and extended for sulu-website.
 */
class RequestAnalyzer implements RequestAnalyzerInterface
{
    public const SULU_ATTRIBUTE = '_sulu';

    /**
     * @param iterable<RequestProcessorInterface> $requestProcessors
     */
    public function __construct(
        private RequestStack $requestStack,
        private iterable $requestProcessors
    ) {
    }

    public function analyze(Request $request)
    {
        if ($request->attributes->has(static::SULU_ATTRIBUTE)) {
            return;
        }

        $attributes = new RequestAttributes(['scheme' => $request->getScheme(), 'requestUri' => $request->getRequestUri()]);
        foreach ($this->requestProcessors as $requestProcessor) {
            $attributes = $attributes->merge($requestProcessor->process($request, $attributes));
        }

        $request->attributes->set(static::SULU_ATTRIBUTE, $attributes);
    }

    public function validate(Request $request)
    {
        $attributes = $request->attributes->get(static::SULU_ATTRIBUTE);

        foreach ($this->requestProcessors as $provider) {
            $provider->validate($attributes);
        }
    }

    public function getAttribute($name, $default = null)
    {
        $requestAttributes = $this->getAttributes();

        if (!$requestAttributes) {
            return $default;
        }

        return $requestAttributes->getAttribute($name, $default);
    }

    private function getAttributes()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        if (!$request->attributes->has(static::SULU_ATTRIBUTE)) {
            return null;
        }

        return $request->attributes->get(static::SULU_ATTRIBUTE);
    }

    private function setAttributes(RequestAttributes $attributes)
    {
        $request = $this->requestStack->getCurrentRequest();

        $request->attributes->set(static::SULU_ATTRIBUTE, $attributes);
    }

    public function getMatchType()
    {
        return $this->getAttribute('matchType');
    }

    public function getDateTime()
    {
        return $this->getAttribute('dateTime');
    }

    public function getWebspace()
    {
        return $this->getAttribute('webspace');
    }

    public function getPortal()
    {
        return $this->getAttribute('portal');
    }

    public function getSegment()
    {
        return $this->getAttribute('segment');
    }

    public function changeSegment(string $segmentKey)
    {
        $segment = $this->getWebspace()->getSegment($segmentKey);

        $requestAttributes = (new RequestAttributes(['segment' => $segment]))->merge($this->getAttributes());

        $this->setAttributes($requestAttributes);
    }

    public function getCurrentLocalization()
    {
        return $this->getAttribute('localization');
    }

    public function getPortalUrl()
    {
        return $this->getAttribute('portalUrl');
    }

    public function getRedirect()
    {
        return $this->getAttribute('redirect');
    }

    public function getResourceLocator()
    {
        return $this->getAttribute('resourceLocator', false);
    }

    public function getResourceLocatorPrefix()
    {
        return $this->getAttribute('resourceLocatorPrefix', '');
    }

    public function getPortalInformation()
    {
        return $this->getAttribute('portalInformation');
    }
}
