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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Form;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;

/**
 * @internal
 */
class SettingsFormMetadataVisitor implements FormMetadataVisitorInterface
{
    /**
     * @var XmlFormMetadataLoader
     */
    private $xmlFormMetadataLoader;

    public function __construct(XmlFormMetadataLoader $xmlFormMetadataLoader)
    {
        $this->xmlFormMetadataLoader = $xmlFormMetadataLoader;
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('content_settings' === $formMetadata->getKey()) {
            foreach ($metadataOptions['forms'] ?? [] as $form) {
                /** @var FormMetadata|null $subFormMetadata */
                $subFormMetadata = $this->xmlFormMetadataLoader->getMetadata($form, $locale, $metadataOptions);

                if (!$subFormMetadata || !($subFormMetadata instanceof FormMetadata)) {
                    continue;
                }

                $formMetadata->setItems(\array_merge($formMetadata->getItems(), $subFormMetadata->getItems()));
                $formMetadata->setSchema($formMetadata->getSchema()->merge($subFormMetadata->getSchema()));
            }
        }
    }
}
