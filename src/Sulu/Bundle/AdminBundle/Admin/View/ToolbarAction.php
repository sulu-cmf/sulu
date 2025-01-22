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

use JMS\Serializer\Annotation\Groups;

class ToolbarAction
{
    /**
     * @var string
     *
     * @Groups({"frontend"})
     */
    private $type;

    /**
     * @var array
     *
     * @Groups({"frontend"})
     */
    private $options;

    public function __construct(string $type, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
