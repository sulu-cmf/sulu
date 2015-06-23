<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    /**
     * Returns Template for the categories list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoriesListAction()
    {
        return $this->render('SuluCategoryBundle:Template:category.list.html.twig');
    }
    /**
     * Returns Template for the details-tab in the category-form.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function categoriesFormDetailsAction()
    {
        return $this->render('SuluCategoryBundle:Template:category.form.details.html.twig');
    }
}
