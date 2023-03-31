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

use Sulu\Bundle\AudienceTargetingBundle\Rule\Type\Input;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This rule determines if the request has been sent in the desired language.
 */
class LocaleRule implements RuleInterface
{
    public const LOCALE = 'locale';

    private \Symfony\Component\HttpFoundation\RequestStack $requestStack;

    private \Symfony\Contracts\Translation\TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function evaluate(array $options)
    {
        if (!isset($options[static::LOCALE])) {
            return false;
        }

        $languages = $this->requestStack->getCurrentRequest()->getLanguages();
        if (!$languages) {
            return false;
        }

        return \substr($languages[0], 0, 2) === \strtolower($options[static::LOCALE]);
    }

    public function getName()
    {
        return $this->translator->trans('sulu_audience_targeting.locale', [], 'admin');
    }

    public function getType()
    {
        return new Input(static::LOCALE);
    }
}
