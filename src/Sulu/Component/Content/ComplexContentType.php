<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use PHPCR\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

abstract class ComplexContentType extends ContainerAware implements ContentTypeInterface
{
    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return  $this->container->get('sulu_core.phpcr.session')->getSession();
    }
}
