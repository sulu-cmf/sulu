<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Form;

use Sulu\Bundle\ArticleBundle\Document\Form\Listener\DataNormalizer;
use Sulu\Bundle\PageBundle\Form\Type\AbstractStructureBehaviorType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form-type for article mapping.
 */
class ArticleDocumentType extends AbstractStructureBehaviorType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // extensions
        $builder->add('extensions', UnstructuredType::class, ['property_path' => 'extensionsData']);
        $builder->add('shadowLocaleEnabled', CheckboxType::class);
        $builder->add('shadowLocale', TextType::class);
        $builder->add('mainWebspace', TextType::class);
        $builder->add('additionalWebspaces', CollectionType::class,
            [
                'entry_type' => TextType::class,
                'entry_options' => [
                    'required' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
            ]
        );

        $builder->add(
            'lastModified',
            DateTimeType::class,
            [
                'widget' => 'single_text',
            ]
        );
        $builder->add('author', TextType::class);
        $builder->add(
            'authored',
            DateTimeType::class,
            [
                'widget' => 'single_text',
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [DataNormalizer::class, 'normalize']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_extra_fields' => true,
            ]
        );
    }
}
