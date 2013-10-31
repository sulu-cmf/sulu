<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal\Dumper;


class PortalCollectionDumper
{
    protected function render($template, $parameters)
    {
        //TODO set path in a more elegant way
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__ . '/../Resources/skeleton/'));

        return $twig->render($template, $parameters);
    }
}
