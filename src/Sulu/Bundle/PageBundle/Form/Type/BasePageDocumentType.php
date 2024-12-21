<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Form\Type;

use Sulu\Component\Content\Form\Type\DocumentObjectType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class BasePageDocumentType extends AbstractStructureBehaviorType
{
    public function configureOptions(OptionsResolver $options)
    {
        $options->setRequired(
            [
                'webspace_key',
            ]
        );

        parent::configureOptions($options);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('extensions', UnstructuredType::class, ['property_path' => 'extensionsData']);
        $builder->add('resourceSegment', TextType::class);
        $builder->add(
            'navigationContexts',
            CollectionType::class,
            [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ]
        );
        $builder->add('redirectType', IntegerType::class);
        $builder->add('redirectTarget', DocumentObjectType::class);
        $builder->add('redirectExternal', TextType::class);
        $builder->add('workflowStage', IntegerType::class);
        $builder->add('shadowLocaleEnabled', CheckboxType::class);
        $builder->add('shadowLocale', TextType::class); // TODO: Should be choice of available shadow locales
        $builder->add(
            'lastModified',
            DateTimeType::class,
            [
                'widget' => 'single_text',
            ]
        );
        $builder->add(
            'authored',
            DateTimeType::class,
            [
                'widget' => 'single_text',
            ]
        );
        $builder->add('author', TextType::class);
        $builder->setAttribute('webspace_key', $options['webspace_key']);
    }
}
