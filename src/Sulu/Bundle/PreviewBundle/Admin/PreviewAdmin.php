<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PreviewAdmin extends Admin
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private int $previewDelay,
        private string $previewMode,
        private array $bundles,
    ) {
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_preview';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'start' => $this->urlGenerator->generate('sulu_preview.start'),
                'render' => $this->urlGenerator->generate('sulu_preview.render'),
                'update' => $this->urlGenerator->generate('sulu_preview.update'),
                'update-context' => $this->urlGenerator->generate('sulu_preview.update-context'),
                'stop' => $this->urlGenerator->generate('sulu_preview.stop'),
                'preview-link' => $this->urlGenerator->generate('sulu_preview.public_preview', ['token' => ':token'], RouterInterface::ABSOLUTE_URL),
            ],
            'debounceDelay' => $this->previewDelay,
            'mode' => $this->previewMode,
            'audienceTargeting' => \array_key_exists('SuluAudienceTargetingBundle', $this->bundles),
        ];
    }
}
