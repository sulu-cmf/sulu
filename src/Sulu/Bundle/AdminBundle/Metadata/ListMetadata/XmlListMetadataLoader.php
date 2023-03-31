<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class XmlListMetadataLoader implements ListMetadataLoaderInterface
{
    private \Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface $fieldDescriptorFactory;

    private \Symfony\Contracts\Translation\TranslatorInterface $translator;

    public function __construct(
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        TranslatorInterface $translator
    ) {
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->translator = $translator;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = [])
    {
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($key);

        if (!$fieldDescriptors) {
            return null;
        }

        $list = new ListMetadata();
        foreach ($fieldDescriptors as $fieldDescriptor) {
            $field = new FieldMetadata($fieldDescriptor->getName());

            $field->setLabel($this->translator->trans($fieldDescriptor->getTranslation(), [], 'admin', $locale));
            $field->setType($fieldDescriptor->getType());
            $field->setVisibility($fieldDescriptor->getVisibility());
            $field->setSortable($fieldDescriptor->getSortable());
            $field->setTransformerTypeParameters($fieldDescriptor->getMetadata()->getTransformerTypeParameters());
            $field->setFilterType($fieldDescriptor->getMetadata()->getFilterType());
            $field->setFilterTypeParameters($fieldDescriptor->getMetadata()->getFilterTypeParameters());
            $field->setWidth($fieldDescriptor->getWidth());

            $list->addField($field);
        }

        return $list;
    }
}
