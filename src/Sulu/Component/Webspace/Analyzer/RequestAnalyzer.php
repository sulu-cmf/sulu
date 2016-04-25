<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
    /**
     * @var RequestProcessorInterface[]
     */
    private $requestProcessors;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack, array $requestProcessors)
    {
        $this->requestStack = $requestStack;
        $this->requestProcessors = $requestProcessors;
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(Request $request)
    {
        $attributes = new RequestAttributes(['host' => $request->getHost(), 'scheme' => $request->getScheme()]);
        foreach ($this->requestProcessors as $provider) {
            $attributes = $attributes->merge($provider->process($request, $attributes));
        }

        foreach ($this->requestProcessors as $provider) {
            $provider->validate($attributes);
        }

        $request->attributes->set('_sulu', $attributes);
    }

    /**
     * Initializes the request attributes lazily.
     *
     * @return RequestAttributes
     */
    protected function getAttributes()
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (null === $request) {
            return new RequestAttributes();
        }
        
        if (null === $request->attributes->get('_sulu')) {
            $this->analyze($this->requestStack->getCurrentRequest());
        }

        return $this->requestStack->getCurrentRequest()->attributes->get('_sulu');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->getAttributes()->getAttribute($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchType()
    {
        return $this->getAttribute('matchType');
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspace()
    {
        return $this->getAttribute('webspace');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortal()
    {
        return $this->getAttribute('portal');
    }

    /**
     * {@inheritdoc}
     */
    public function getSegment()
    {
        return $this->getAttribute('segment');
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentLocalization()
    {
        return $this->getAttribute('localization');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalUrl()
    {
        return $this->getAttribute('portalUrl');
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect()
    {
        return $this->getAttribute('redirect');
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocator()
    {
        return $this->getAttribute('resourceLocator');
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceLocatorPrefix()
    {
        return $this->getAttribute('resourceLocatorPrefix');
    }

    /**
     * {@inheritdoc}
     */
    public function getPostParameters()
    {
        return $this->getAttribute('postParameter', []);
    }

    /**
     * {@inheritdoc}
     */
    public function getGetParameters()
    {
        return $this->getAttribute('getParameter', []);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticsKey()
    {
        return $this->getAttribute('analyticsKey', '');
    }

    /**
     * {@inheritdoc}
     */
    public function getPortalInformation()
    {
        return $this->getAttribute('portalInformation');
    }
}
