<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * handles snippet template.
 */
class SnippettypesController extends Controller implements ClassResourceInterface
{
    use RequestParametersTrait;

    /**
     * Returns all snippet types.
     *
     * @return JsonResponse
     */
    public function cgetAction(Request $request)
    {
        // TODO convert uuid into title (localized)

        $defaults = $this->getBooleanRequestParameter($request, 'defaults');
        $webspaceKey = $this->getRequestParameter($request, 'webspace', $defaults);

        /** @var StructureManagerInterface $structureManager */
        $structureManager = $this->get('sulu.content.structure_manager');
        $types = $structureManager->getStructures(Structure::TYPE_SNIPPET);

        $templates = [];
        foreach ($types as $type) {
            $template = [
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
            ];

            if ($defaults) {
                $default = $this->get('sulu_core.webspace.settings_manager')->load(
                    $webspaceKey,
                    'snippets.' . $type->getKey()
                );

                $template['default'] = !$default ? null : $default->getIdentifier();
            }

            $templates[] = $template;
        }

        $data = [
            '_embedded' => $templates,
            'total' => count($templates),
        ];

        return new JsonResponse($data);
    }

    public function putDefaultAction($key, Request $request)
    {
        $default = $request->get('default');
        $webspaceKey = $this->getRequestParameter($request, 'webspace', true);

        $type = $this->get('sulu.content.structure_manager')->getStructure($key, Structure::TYPE_SNIPPET);

        $node = $this->get('sulu_document_manager.node_manager')->find($default);

        $this->get('sulu_core.webspace.settings_manager')->save(
            $webspaceKey,
            'snippets.' . $key,
            $node
        );

        return new JsonResponse(
            [
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale()),
                'default' => $default,
            ]
        );
    }
}
