<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Teaser\Configuration;

use JMS\Serializer\Annotation\Groups;

class TeaserConfiguration
{
    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $title;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $resourceKey;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $listAdapter;

    /**
     * @var string[]
     */
    #[Groups(['frontend'])]
    private $displayProperties;

    /**
     * @var string
     */
    #[Groups(['frontend'])]
    private $overlayTitle;

    /**
     * @var string|null
     */
    #[Groups(['frontend'])]
    private $view;

    /**
     * @var array<string, string>|null
     */
    #[Groups(['frontend'])]
    private $resultToView;

    /**
     * @param array<string, string>|null $resultToView
     */
    public function __construct(
        string $title,
        string $resourceKey,
        string $listAdapter,
        array $displayProperties,
        string $overlayTitle,
        ?string $view = null,
        ?array $resultToView = null
    ) {
        $this->title = $title;
        $this->resourceKey = $resourceKey;
        $this->listAdapter = $listAdapter;
        $this->displayProperties = $displayProperties;
        $this->overlayTitle = $overlayTitle;
        $this->view = $view;
        $this->resultToView = $resultToView;
    }
}
