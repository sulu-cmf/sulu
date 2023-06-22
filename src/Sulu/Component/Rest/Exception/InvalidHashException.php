<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

/**
 * Exception, which is thrown when the given hash does not match the hash of the current object. Usually happens when
 * the data has been changed since it has been loaded.
 */
class InvalidHashException extends RestException
{
    /**
     * @param string $entity
     * @param mixed $id
     */
    public function __construct(private $entity, private $id)
    {
        parent::__construct(
            \sprintf(
                'The given hash for the entity of type "%s" with the id "%s" does not match the current hash.'
                . ' The entity has probably been edited in the mean time.',
                $entity,
                $id
            ),
            static::EXCEPTION_CODE_INVALID_HASH
        );
    }

    /**
     * Returns the entity for which an invalid hash has been passed.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the id of the entity for which an invalid hash has been passed.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
