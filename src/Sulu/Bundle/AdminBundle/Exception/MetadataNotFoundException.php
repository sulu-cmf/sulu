<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

class MetadataNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $key;

    public function __construct(string $type, string $key)
    {
        $this->type = $type;
        $this->key = $key;

        parent::__construct(
            sprintf('There is no Metadata available for the type "%s" with the key "%s".', $this->type, $this->key)
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
