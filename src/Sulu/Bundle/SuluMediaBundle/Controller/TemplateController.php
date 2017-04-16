<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles templates for this bundles
 */
class TemplateController extends Controller
{

    /**
     * renders template for the collection files-view
     * @return Response
     */
    public function collectionFilesAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-files.html.twig');
    }

    /**
     * renders template for the collection settings-view
     * @return Response
     */
    public function collectionSettingsAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-settings.html.twig');
    }

    /**
     * renders template for the media info-view
     * @return Response
     */
    public function mediaInfoAction()
    {
        return $this->render('SuluMediaBundle:Template:media-info.html.twig');
    }

    /**
     * renders template for the new-collection-form
     * @return Response
     */
    public function collectionNewAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-new.html.twig');
    }

    /**
     * renders template for a media in the multiple-edit form
     * @return Response
     */
    public function multipleEditAction()
    {
        return $this->render('SuluMediaBundle:Template:media-multiple-edit.html.twig');
    }

    /**
     * renders template for the collection list
     * @return Response
     */
    public function collectionListAction()
    {
        return $this->render('SuluMediaBundle:Template:collection-list.html.twig');
    }

}
