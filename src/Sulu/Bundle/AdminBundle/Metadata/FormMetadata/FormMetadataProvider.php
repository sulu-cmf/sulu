<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;

/**
 * @internal This class should not be extended or initialized by any application outside of sulu.
 *           You can inject custom loaders or visitors to adjust the behaviour of the service in your project.
 */
class FormMetadataProvider implements MetadataProviderInterface
{
    /**
     * @var iterable<FormMetadataLoaderInterface>
     */
    private $formMetadataLoaders;

    /**
     * @var iterable<FormMetadataVisitorInterface>
     */
    private $formMetadataVisitors;

    /**
     * @var iterable<TypedFormMetadataVisitorInterface>
     */
    private $typedFormMetadataVisitors;

    /**
     * @param iterable<FormMetadataLoaderInterface> $formMetadataLoaders
     * @param iterable<FormMetadataVisitorInterface> $formMetadataVisitors
     * @param iterable<TypedFormMetadataVisitorInterface> $typedFormMetadataVisitors
     */
    public function __construct(
        iterable $formMetadataLoaders,
        iterable $formMetadataVisitors,
        iterable $typedFormMetadataVisitors
    ) {
        $this->formMetadataLoaders = $formMetadataLoaders;
        $this->formMetadataVisitors = $formMetadataVisitors;
        $this->typedFormMetadataVisitors = $typedFormMetadataVisitors;
    }

    public function getMetadata(string $key, string $locale, array $metadataOptions = []): MetadataInterface
    {
        $formMetadata = null;
        foreach ($this->formMetadataLoaders as $metadataLoader) {
            $formMetadata = $metadataLoader->getMetadata($key, $locale, $metadataOptions);
            if ($formMetadata) {
                break;
            }
        }
        if (!$formMetadata) {
            throw new MetadataNotFoundException('form', $key);
        }

        if ($formMetadata instanceof FormMetadata) {
            foreach ($this->formMetadataVisitors as $formMetadataVisitor) {
                $formMetadataVisitor->visitFormMetadata($formMetadata, $locale, $metadataOptions);
            }
        } elseif ($formMetadata instanceof TypedFormMetadata) {
            foreach ($this->typedFormMetadataVisitors as $typedFormMetadataVisitor) {
                $typedFormMetadataVisitor->visitTypedFormMetadata($formMetadata, $key, $locale, $metadataOptions);
            }
        }

        return $formMetadata;
    }
}
