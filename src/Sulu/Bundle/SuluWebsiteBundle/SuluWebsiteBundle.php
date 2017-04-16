<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle;

use Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler\RouteProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluWebsiteBundle extends Bundle
{
    public function build(ContainerBuilder $container) {
        parent::build($container);

        $container->addCompilerPass(new RouteProviderCompilerPass());
    }
}
