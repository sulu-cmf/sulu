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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentMerger\Merger;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\DimensionContentMerger;
use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class DimensionContentMergerTest extends TestCase
{
    protected function getDimensionContentMergerInstance(): MergerInterface
    {
        return new DimensionContentMerger();
    }

    public function testMergeTargetNotImplementDimensionContentInterface(): void
    {
        $merger = $this->getDimensionContentMergerInstance();

        $source = new \stdClass();
        $target = new \stdClass();
        $target->ghostLocale = 'fr';

        $merger->merge($target, $source);

        $this->assertSame('fr', $target->ghostLocale);
    }

    public function testMergeSourceNotImplementDimensionContentInterface(): void
    {
        $merger = $this->getDimensionContentMergerInstance();

        $example = new Example();
        $source = new \stdClass();
        $target = new ExampleDimensionContent($example);
        $target->setGhostLocale('fr');

        $merger->merge($target, $source);

        $this->assertSame('fr', $target->getGhostLocale());
    }

    public function testMergeSet(): void
    {
        $example = new Example();
        $source = new ExampleDimensionContent($example);
        $source->setGhostLocale('en');
        $source->addAvailableLocale('en');
        $source->addAvailableLocale('de');
        $target = new ExampleDimensionContent($example);

        $merger = $this->getDimensionContentMergerInstance();
        $merger->merge($target, $source);

        $this->assertSame('en', $target->getGhostLocale());
        $this->assertSame(['en', 'de'], $target->getAvailableLocales());
    }

    public function testMergeNotSet(): void
    {
        $example = new Example();
        $source = new ExampleDimensionContent($example);
        $target = new ExampleDimensionContent($example);
        $target->setGhostLocale('en');
        $target->addAvailableLocale('en');
        $target->addAvailableLocale('de');

        $merger = $this->getDimensionContentMergerInstance();
        $merger->merge($target, $source);

        $this->assertSame('en', $target->getGhostLocale());
        $this->assertSame(['en', 'de'], $target->getAvailableLocales());
    }

    public function testMergeBoth(): void
    {
        $example = new Example();
        $source = new ExampleDimensionContent($example);
        $source->setGhostLocale('fr');
        $source->addAvailableLocale('fr');
        $target = new ExampleDimensionContent($example);
        $target->setGhostLocale('en');
        $target->addAvailableLocale('en');
        $target->addAvailableLocale('de');

        $merger = $this->getDimensionContentMergerInstance();
        $merger->merge($target, $source);

        $this->assertSame('fr', $target->getGhostLocale());
        $this->assertSame(['en', 'de', 'fr'], $target->getAvailableLocales());
    }
}
