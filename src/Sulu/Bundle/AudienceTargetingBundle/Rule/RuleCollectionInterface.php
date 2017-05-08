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

interface RuleCollectionInterface
{
    /**
     * Returns the rule with the given name.
     *
     * @param string $name
     *
     * @return RuleInterface
     *
     * @throws RuleNotFoundException
     */
    public function getRule($name);
}
