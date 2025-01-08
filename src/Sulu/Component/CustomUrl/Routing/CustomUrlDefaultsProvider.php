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

namespace Sulu\Component\CustomUrl\Routing;

use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\WebsiteBundle\Controller\RedirectController as SuluRedirectController;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class CustomUrlDefaultsProvider
{
    public function __construct(
        private DocumentManagerInterface $documentManager,
        private DocumentInspector $inspector,
        private StructureManagerInterface $structureManager,
        private WebspaceManagerInterface $webspaceManager
    ) {
    }

    public function provideDefault(Request $request, CustomUrl $customUrl): array
    {
        $seo = [
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
        ];

        if ($customUrl->isCanonical()) {
            $document = $this->documentManager->find($customUrl->getTargetDocument());
            $resourceSegment = $document->getResourceSegment();
            $seo['canonicalUrl'] = $this->webspaceManager->findUrlByResourceLocator(
                $resourceSegment,
                null,
                $customUrl->getTargetLocale(),
                $customUrl->getWebspace(),
                $request->getHost(),
                $request->getScheme()
            );
        }

        return [
            '_seo' => $seo,
            '_webspace' => $this->webspaceManager->findWebspaceByKey($customUrl->getWebspace()),
            '_environment' => 'dev',
        ];
    }

    public function provideForRedirect(Request $request, CustomUrl $customUrl): array
    {
        $resourceSegment = '/';
        if (null !== $customUrl->getTargetDocument()) {
            $document = $this->documentManager->find($customUrl->getTargetDocument());
            $resourceSegment = $document->getResourceSegment();
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            resourceLocator: $resourceSegment,
            environment: null,
            languageCode: $customUrl->getTargetLocale(),
            webspaceKey: null,
            domain: $request->getHost(),
            scheme: $request->getScheme()
        );

        $requestFormat = $request->getRequestFormat(null);
        $requestFormatSuffix = $requestFormat ? '.' . $requestFormat : '';

        $queryString = $request->getQueryString();
        $queryStringSuffix = $queryString ? '?' . $queryString : '';

        return [
            '_controller' => SuluRedirectController::class . '::redirectAction',
            'url' => $url . $requestFormatSuffix . $queryStringSuffix,
        ];
    }

    public function provideForForward(Request $request, CustomUrl $customUrl): array
    {
        $document = $this->documentManager->find($customUrl->getTargetDocument());

        //if ($document->getNodeType() === Structure::NODE_TYPE_EXTERNAL_LINK) {
        //return [
        //'_controller' => [SuluRedirectController, 'redirectAction'],
        //'url' => $document->getResourceLocator(),
        //];
        //} else if ($document->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK) {
        //$structure = $document->getInternalLinkContent();
        //} else {
        //}

        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return [
            '_controller' => $structure->getController(),
            'structure' => $structureBridge,
            '_structure' => $structureBridge,
        ];
    }
}
