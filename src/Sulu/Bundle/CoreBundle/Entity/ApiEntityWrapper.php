<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Entity;

use JMS\Serializer\Annotation\Exclude;

/**
 * The abstract base class for an API object, which wraps another entity.
 *
 * @deprecated
 */
class ApiEntityWrapper
{
    /**
     * the entity which is wrapped by this class.
     *
     * @var object
     */
    #[Exclude]
    protected $entity;

    /**
     * the locale in which the wrapped entity should be expressed.
     *
     * @var string
     */
    protected $locale;

    public function getEntity()
    {
        return $this->entity;
    }
}
