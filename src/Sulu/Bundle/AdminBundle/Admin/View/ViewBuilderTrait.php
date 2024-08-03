<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

trait ViewBuilderTrait
{
    /**
     * @var View
     */
    private $view;

    public function getName(): string
    {
        return $this->view->getName();
    }

    public function setType(string $type): ViewBuilderInterface
    {
        $this->view->setType($type);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOption(string $key, $value): ViewBuilderInterface
    {
        $this->view->setOption($key, $value);

        return $this;
    }

    public function setAttributeDefault(string $key, string $value): ViewBuilderInterface
    {
        $this->view->setAttributeDefault($key, $value);

        return $this;
    }

    public function setParent(string $parent): ViewBuilderInterface
    {
        $this->view->setParent($parent);

        return $this;
    }

    public function addRerenderAttribute(string $attribute): ViewBuilderInterface
    {
        $this->view->addRerenderAttribute($attribute);

        return $this;
    }
}
