<?php

declare(strict_types=1);

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Visitor;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;

class LocalesOptionFormMetadataVisitor implements FormMetadataVisitorInterface
{
    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('ghost_copy_locale' !== $formMetadata->getKey() && 'copy_locale' !== $formMetadata->getKey()) {
            return;
        }

        $locales = $metadataOptions['locales'] ?? []; // TODO get all locales
        $defaultValue = $locales[0] ?? null;

        $defaultValueOption = new OptionMetadata();
        $defaultValueOption->setName('default_value');
        $defaultValueOption->setValue($defaultValue);

        $valuesOption = new OptionMetadata();
        $valuesOption->setName('values');
        $valuesOption->setType('collection');
        $valuesOption->setValue(\array_map(function ($locale) {
            $option = new OptionMetadata();
            $option->setName($locale);
            $option->setValue($locale);
            $option->setTitle($locale);

            return $option;
        }, $locales));

        /** @var FieldMetadata $localeProperty */
        $localeProperty = $formMetadata->getItems()['locale'] ?? $formMetadata->getItems()['locales'];
        $localeProperty->addOption($defaultValueOption);
        $localeProperty->addOption($valuesOption);
    }
}
