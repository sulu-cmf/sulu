<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Request;

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestProcessorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set localization in case of custom-url route.
 */
class CustomUrlRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var CustomUrlManagerInterface
     */
    private $customUrlManager;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        CustomUrlManagerInterface $customUrlManager,
        GeneratorInterface $generator,
        WebspaceManagerInterface $webspaceManager,
        $environment
    ) {
        $this->customUrlManager = $customUrlManager;
        $this->generator = $generator;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $url = $this->decodeUrl(rtrim(sprintf('%s%s', $request->getHost(), $request->getRequestUri()), '/'));
        if (substr($url, -5, 5) === '.html') {
            $url = substr($url, 0, -5);
        }
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl($url, $this->environment);

        if (count($portalInformations) === 0) {
            return new RequestAttributes();
        }

        /** @var PortalInformation[] $portalInformations */
        $portalInformations = array_filter(
            $portalInformations,
            function (PortalInformation $portalInformation) {
                return $portalInformation->getType() === RequestAnalyzer::MATCH_TYPE_WILDCARD;
            }
        );

        foreach ($portalInformations as $portalInformation) {
            if (!$portalInformation->getWebspace()) {
                continue;
            }

            if (null !== $attributes = $this->matchCustomUrl($url, $portalInformation, $request)) {
                return new RequestAttributes($attributes);
            }
        }

        return new RequestAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(RequestAttributes $attributes)
    {
        return true;
    }

    /**
     * Matches given url to portal-information.
     *
     * @param string $url
     * @param PortalInformation $portalInformation
     * @param Request $request
     *
     * @return array
     */
    private function matchCustomUrl($url, PortalInformation $portalInformation, Request $request)
    {
        $webspace = $portalInformation->getWebspace();
        $routeDocument = $this->customUrlManager->findRouteByUrl(
            rawurldecode($url),
            $webspace->getKey()
        );

        if (!$routeDocument) {
            return [];
        } elseif ($routeDocument->isHistory()) {
            // redirect happen => no portal is needed
            return ['customUrlRoute' => $routeDocument];
        }

        $customUrlDocument = $this->customUrlManager->findByUrl(
            rawurldecode($url),
            $webspace->getKey(),
            $routeDocument->getTargetDocument()->getTargetLocale()
        );

        if ($customUrlDocument === null
            || $customUrlDocument->isPublished() === false
            || $customUrlDocument->getTargetDocument() === null
            || $customUrlDocument->getTargetDocument()->getWorkflowStage() !== WorkflowStage::PUBLISHED
        ) {
            // error happen because this custom-url is not published => no portal is needed
            return ['customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument];
        }

        $localization = Localization::createFromString($customUrlDocument->getTargetLocale());

        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            $portalInformation->getWebspace()->getKey(),
            $localization->getLocale(),
            $this->environment
        );

        if (0 === count($portalInformations)) {
            return ['customUrlRoute' => $routeDocument, 'customUrl' => $customUrlDocument];
        }

        return [
            'portalInformation' => $portalInformation,
            'localization' => $localization,
            'locale' => $localization->getLocale(),
            'customUrlRoute' => $routeDocument,
            'customUrl' => $customUrlDocument,
            'urlExpression' => $this->generator->generate(
                $customUrlDocument->getBaseDomain(),
                $customUrlDocument->getDomainParts()
            ),
        ];
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     *
     * @param $pathInfo
     *
     * @return string
     */
    private function decodeUrl($pathInfo)
    {
        return rawurldecode($pathInfo);
    }
}
