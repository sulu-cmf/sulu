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

/**
 * Interface for rules, which should help to find a matching target group.
 */
interface RuleInterface
{
    /**
     * Returns a string representation of the evaluation of the rule for the current context.
     *
     * @param array $options The options to evaluate against
     *
     * @return bool
     */
    public function evaluate(array $options);

    /**
     * Returns the translated name for the given Rule.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the path for the twig template to render. The result of the twig template will be.
     *
     * @return string
     */
    public function getTemplate();
}
