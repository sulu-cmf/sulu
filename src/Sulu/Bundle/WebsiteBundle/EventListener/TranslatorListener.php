<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class TranslatorListener implements EventSubscriberInterface
{
    /**
     * @var Translator|LocaleAwareInterface
     */
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $attributes = $event->getRequest()->attributes->get('_sulu');

        if (!$attributes instanceof RequestAttributes) {
            return;
        }

        $localization = $attributes->getAttribute('localization');

        if (!$localization instanceof Localization) {
            return;
        }

        $this->translator->setLocale($localization->getLocale(Localization::LCID));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Set the translator locale in `de_AT` format instead of `de-at`
            KernelEvents::REQUEST => [['onKernelRequest', 14]],
        ];
    }
}
