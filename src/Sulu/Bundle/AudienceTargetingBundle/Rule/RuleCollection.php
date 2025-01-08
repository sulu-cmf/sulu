<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

/**
 * Contains all the available audience targeting rules in this system.
 */
class RuleCollection implements RuleCollectionInterface
{
    /**
     * @var array<string, RuleInterface>
     */
    private $rules;

    /**
     * @param iterable<string, RuleInterface> $rules
     */
    public function __construct(iterable $rules)
    {
        $this->rules = [...$rules];
    }

    public function getRule($name)
    {
        if (!isset($this->rules[$name])) {
            throw new RuleNotFoundException($name);
        }

        return $this->rules[$name];
    }

    public function getRules()
    {
        return $this->rules;
    }
}
