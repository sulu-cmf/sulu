<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\DependencyInjection\Compiler;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Get all snippets from compiler.
 */
class SnippetAreaCompilerPass implements CompilerPassInterface
{
    /**
     * @return void
     */
    public function process(ContainerBuilder $container): void
    {
        $structureFactory = $container->get('sulu_page.structure.factory');
        $structures = $structureFactory->getStructures(Structure::TYPE_SNIPPET);

        $locales = $container->getParameter('sulu_core.locales');

        $defaultAreas = [];
        $areas = [];

        /** @var StructureMetadata $structure */
        foreach ($structures as $structure) {
            $template = $structure->getName();

            $templateTitles = [];
            foreach ($locales as $locale) {
                $templateTitles[$locale] = $structure->getTitle($locale);
            }

            $defaultAreas[$template] = [
                'key' => $template,
                'template' => $template,
                'title' => $templateTitles,
                'cache-invalidation' => 'false',
            ];

            foreach ($structure->getAreas() as $area) {
                $area = $this->getArea($container, (string) $template, $area, $locales, $templateTitles);

                if (isset($areas[$area['key']])) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Snippet area "%s" need to be unique it is defined in "%s" and "%s"',
                        $area['key'],
                        $areas[$area['key']]['template'] . '.xml',
                        $area['template'] . '.xml'
                    ));
                }

                $areas[$area['key']] = $area;
            }
        }

        if (empty($areas)) {
            $areas = $defaultAreas;
        }

        \ksort($areas);

        $container->setParameter('sulu_snippet.areas', $areas);
    }

    /**
     * @param array{key: string, title: array<string>, cache-invalidation: string} $area
     * @param array<string> $locales
     * @param array<string, string> $templateTitles
     *
     * @return array{key: string, template: string, title: array<string, string>}
     */
    private function getArea(ContainerBuilder $container, string $template, array $area, array $locales, array $templateTitles): array
    {
        $key = $area['key'];
        $cacheInvalidation = $area['cache-invalidation'];

        $titles = [];
        $areaTitles = $area['title'];

        // If we only have one title and no locale (indexed 0) then it's a translation key
        if (1 === \count($areaTitles) && \array_key_exists(0, $areaTitles)) {
            $translator = $container->get('translator');
            $titleToTranslate = \reset($areaTitles);
            foreach ($locales as $locale) {
                $titles[$locale] = $translator->trans($titleToTranslate, [], 'admin', $locale);
            }
        } else {
            foreach ($locales as $locale) {
                $title = $templateTitles[$locale] . ' ' . \ucfirst($key);
                if (isset($areaTitles[$locale])) {
                    $title = $areaTitles[$locale];
                }

                $titles[$locale] = $title;
            }
        }

        return [
            'key' => $key,
            'template' => $template,
            'title' => $titles,
            'cache-invalidation' => $cacheInvalidation,
        ];
    }
}
