<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

class RuleCollection implements RuleCollectionInterface
{
    /**
     * @var RuleInterface[]
     */
    private $rules;

    /**
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getRule($name)
    {
        if (!isset($this->rules[$name])) {
            throw new RuleNotFoundException($name);
        }

        return $this->rules[$name];
    }
}
