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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Visitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor\LocalesOptionFormMetadataVisitor;

class LocalesOptionFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    private LocalesOptionFormMetadataVisitor $localesOptionFormMetadataVisitor;

    protected function setUp(): void
    {
        $this->localesOptionFormMetadataVisitor = new LocalesOptionFormMetadataVisitor();
    }

    public function testVisitFormMetadata(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('ghost_copy_locale');

        $localeProperty = new FieldMetadata('locale');
        $formMetadata->addItem($localeProperty);

        $this->localesOptionFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', ['locales' => ['en', 'de']]);

        $this->assertSame('en', $localeProperty->getOptions()['default_value']->getValue());
        $this->assertSame('collection', $localeProperty->getOptions()['values']->getType());
        $this->assertCount(2, $localeProperty->getOptions()['values']->getValue());
        $this->assertSame('en', $localeProperty->getOptions()['values']->getValue()[0]->getValue());
        $this->assertSame('de', $localeProperty->getOptions()['values']->getValue()[1]->getValue());
    }

    public function testVisitFormMetadataWithCopyLocale(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('copy_locale');

        $localeProperty = new FieldMetadata('locales');
        $formMetadata->addItem($localeProperty);

        $this->localesOptionFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', ['locales' => ['en', 'de']]);

        $this->assertSame('collection', $localeProperty->getOptions()['values']->getType());
        $this->assertCount(2, $localeProperty->getOptions()['values']->getValue());
        $this->assertSame('en', $localeProperty->getOptions()['values']->getValue()[0]->getValue());
        $this->assertSame('de', $localeProperty->getOptions()['values']->getValue()[1]->getValue());
    }
}
