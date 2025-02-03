<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection\Compiler;

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class WebspacesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $directory = $container->getParameterBag()->resolveValue($container->getParameter('sulu_core.webspace.config_dir'));

        if (!$container->hasExtension('sulu_search') || !\file_exists($directory)) {
            return;
        }

        $indexes = $container->getParameter('sulu_search.indexes');

        $finder = new Finder();
        $finder->in($directory)->files()->name('*.xml')->sortByName();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $webspaceConfig = \simplexml_load_file($file->getPathName());
            $webspaceConfig->registerXPathNamespace('x', 'http://schemas.sulu.io/webspace/webspace');
            $webspaceKey = (string) $webspaceConfig->xpath('/x:webspace/x:key')[0];
            $webspaceName = (string) $webspaceConfig->xpath('/x:webspace/x:name')[0];
            $indexes['page_' . $webspaceKey] = [
                'name' => $webspaceName,
                'icon' => 'su-document',
                'view' => [
                    'name' => PageAdmin::EDIT_FORM_VIEW,
                    'result_to_view' => ['id' => 'id', 'locale' => 'locale', 'properties/webspace_key' => 'webspace'],
                ],
                'security_context' => 'sulu.webspaces.' . $webspaceKey,
            ];
            $indexes['page_' . $webspaceKey . '_published'] = [
                'name' => $webspaceName,
                'icon' => 'su-document',
                'view' => [
                    'name' => PageAdmin::EDIT_FORM_VIEW,
                    'result_to_view' => ['id' => 'id', 'locale' => 'locale', 'properties/webspace_key' => 'webspace'],
                ],
                'security_context' => 'sulu.webspaces.' . $webspaceKey,
                'contexts' => ['website'],
            ];
        }

        $container->setParameter('sulu_search.indexes', $indexes);
    }
}
