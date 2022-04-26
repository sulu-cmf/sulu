<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Generator;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class TranslatorWrapper implements TranslatorInterface, LocaleAwareInterface
{
    /**
     * @var TranslatorInterface&LocaleAwareInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface&LocaleAwareInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param mixed[] $parameters
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function setLocale($locale): void
    {
        throw new \LogicException('Not supported.');
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}
