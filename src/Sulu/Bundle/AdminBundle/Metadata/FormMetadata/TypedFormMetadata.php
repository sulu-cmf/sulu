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

use JMS\Serializer\Annotation\SerializedName;
use Sulu\Bundle\AdminBundle\Metadata\AbstractMetadata;

class TypedFormMetadata extends AbstractMetadata
{
    /**
     * @var FormMetadata[]
     */
    #[SerializedName('types')]
    private $forms = [];

    /**
     * @var string
     */
    private $defaultType;

    public function addForm($key, FormMetadata $form): void
    {
        $this->forms[$key] = $form;
    }

    public function removeForm($key)
    {
        unset($this->forms[$key]);
    }

    /**
     * @return FormMetadata[]
     */
    public function getForms(): array
    {
        return $this->forms;
    }

    public function setDefaultType(string $defaultType): void
    {
        $this->defaultType = $defaultType;
    }

    public function getDefaultType(): string
    {
        return $this->defaultType;
    }
}
