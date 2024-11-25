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

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\FormMetadata\FormXmlLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

class SettingsFormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $formDirectories = $container->getParameter('sulu_admin.forms.directories');

        $finder = new Finder();
        $finder->files()->in($formDirectories)->name('*.xml');
        $settingsForms = [];
        foreach ($finder as $file) {
            $document = new \DOMDocument();
            $document->load($file->getPathname());
            $xPath = new \DOMXPath($document);
            $xPath->registerNamespace('x', FormXmlLoader::SCHEMA_NAMESPACE_URI);
            $tagNodes = $xPath->query('/x:form/x:tag');
            $keyNodes = $xPath->query('/x:form/x:key');

            if (!$tagNodes || 0 === $tagNodes->count()) {
                continue;
            }

            $key = null;
            if ($keyNodes && $keyNodes->count() > 0) {
                $item = $keyNodes->item(0);
                $key = $item->nodeValue ?? null;
            }

            if (!$keyNodes || !$key) {
                throw new \RuntimeException(
                    'Forms must have a valid "key" element!'
                );
            }

            /** @var \DOMElement $tagNode */
            foreach ($tagNodes as $tagNode) {
                $instanceOf = $tagNode->getAttribute('instanceOf');
                $priority = $tagNode->getAttribute('priority');

                if ('sulu_content.content_settings_form' !== $tagNode->getAttribute('name')) {
                    continue;
                }

                if (empty($instanceOf)) {
                    throw new \RuntimeException(
                        'Tags with the name "sulu_content.content_settings_form" must have a valid "instanceOf" attribute! '
                    );
                }

                $settingsForms[$key] = [
                    'instanceOf' => $instanceOf,
                    'priority' => (int) $priority,
                ];
            }
        }

        \uasort($settingsForms, static function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        $container->setParameter('sulu_content.content_settings_forms', $settingsForms);
    }
}
