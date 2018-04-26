<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\SnippetBundle\DependencyInjection\SuluSnippetExtension;

class SuluSnippetExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SuluSnippetExtension(),
        ];
    }

    public function testLoad()
    {
        $this->load([
            'twig' => [
                'snippet' => [
                    'cache_lifetime' => 20,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('sulu_snippet.twig.snippet.cache_lifetime', 20);
    }
}
