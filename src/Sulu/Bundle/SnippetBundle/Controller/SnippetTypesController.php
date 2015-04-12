<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sulu\Component\Content\Compat\Stucture\LegacyStructureConstants;

/**
 * handles snippet template
 */
class SnippetTypesController extends Controller implements ClassResourceInterface
{
    /**
     * Returns all snippet types
     * @return JsonResponse
     */
    public function cgetAction()
    {
        /** @var StructureFactoryInterface $structureFactory */
        $structureFactory = $this->get('sulu_content.structure.factory');
        $types = $structureFactory->getStructures(LegacyStructureConstants::TYPE_SNIPPET);

        $templates = array();
        foreach ($types as $type) {
            $templates[] = array(
                'template' => $type->getKey(),
                'title' => $type->getLocalizedTitle($this->getUser()->getLocale())
            );
        }

        $data = array(
            '_embedded' => $templates,
            'total' => sizeof($templates)
        );

        return new JsonResponse($data);
    }
}
