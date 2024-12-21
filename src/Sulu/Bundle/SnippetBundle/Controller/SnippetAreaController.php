<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Util\SortUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Handles snippet types and defaults.
 *
 * @RouteResource("snippet-area")
 */
class SnippetAreaController implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * @param array<array{key: string, title: array<string, string>, template: string}> $sulu_snippet_areas
     */
    public function __construct(
        protected DefaultSnippetManagerInterface $defaultSnippetManager,
        protected DocumentManagerInterface $documentManager,
        protected SecurityCheckerInterface $securityChecker,
        protected TokenStorageInterface $tokenStorage,
        protected array $sulu_snippet_areas
    ) {
    }

    protected function getUser()
    {
        $user = null;
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
        }

        return $user;
    }

    /**
     * Get snippet areas.
     *
     * @return JsonResponse
     */
    public function cgetAction(Request $request)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->securityChecker->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::VIEW
        );

        $areas = $this->getLocalizedAreas();

        $dataList = [];
        foreach ($areas as $key => $area) {
            $areaData = [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => null,
                'defaultTitle' => null,
                'valid' => true,
            ];

            try {
                $snippet = $this->defaultSnippetManager->load($webspaceKey, $key, $this->getUser()->getLocale());
                $areaData['defaultUuid'] = $snippet ? $snippet->getUuid() : null;
                $areaData['defaultTitle'] = $snippet ? $snippet->getTitle() : null;
            } catch (WrongSnippetTypeException $exception) {
                // ignore wrong snippet-type
                $areaData['valid'] = false;

                $uuid = $this->defaultSnippetManager->loadIdentifier($webspaceKey, $key);

                if ($uuid) {
                    $snippet = $this->documentManager->find($uuid, $this->getUser()->getLocale());
                    $areaData['defaultUuid'] = null;
                    $areaData['defaultTitle'] = null;
                }
            }

            $dataList[$key] = $areaData;
        }

        $dataList = SortUtils::sortLocaleAware($dataList, $this->getUser()->getLocale(), fn ($a) => $a['title']);

        $data = [
            '_embedded' => [
                'areas' => \array_values($dataList),
            ],
            'total' => \count($dataList),
        ];

        return new JsonResponse($data);
    }

    /**
     * Put default action.
     *
     * @param string $key
     *
     * @return JsonResponse
     */
    public function putAction(Request $request, $key)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->securityChecker->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $default = $request->get('defaultUuid');

        $areas = $this->getLocalizedAreas();
        $area = $areas[$key];

        $defaultSnippet = $this->defaultSnippetManager->save(
            $webspaceKey,
            $key,
            $default,
            $this->getUser()->getLocale()
        );

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => $defaultSnippet->getUuid(),
                'defaultTitle' => $defaultSnippet->getTitle(),
                'valid' => true,
            ]
        );
    }

    /**
     * Delete default action.
     *
     * @param string $key
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $key)
    {
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);
        $this->securityChecker->checkPermission(
            new SecurityCondition(SnippetAdmin::getDefaultSnippetsSecurityContext($webspaceKey)),
            PermissionTypes::EDIT
        );

        $areas = $this->getLocalizedAreas();
        $area = $areas[$key];

        $this->defaultSnippetManager->remove($webspaceKey, $key);

        return new JsonResponse(
            [
                'key' => $key,
                'template' => $area['template'],
                'title' => $area['title'],
                'defaultUuid' => null,
                'defaultTitle' => null,
                'valid' => true,
            ]
        );
    }

    /**
     * Get snippet areas.
     *
     * @return array
     */
    private function getLocalizedAreas()
    {
        $areas = $this->sulu_snippet_areas;
        $locale = $this->getUser()->getLocale();

        $localizedAreas = [];

        foreach ($areas as $type) {
            $title = \ucfirst($type['key']);

            if (isset($type['title'][$locale])) {
                $title = $type['title'][$locale];
            }

            $localizedAreas[$type['key']] = [
                'title' => $title,
                'template' => $type['template'],
            ];
        }

        return $localizedAreas;
    }
}
