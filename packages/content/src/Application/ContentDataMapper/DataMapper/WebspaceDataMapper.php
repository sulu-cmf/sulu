<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;

class WebspaceDataMapper implements DataMapperInterface
{
    /**
     * @var string|null
     */
    private $defaultWebspaceKey;

    public function __construct(private WebspaceManagerInterface $webspaceManager)
    {
    }

    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof WebspaceInterface) {
            return;
        }

        $this->setWebspaceData($localizedDimensionContent, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setWebspaceData(WebspaceInterface $dimensionContent, array $data): void
    {
        // TODO allow to configure another webspace with `<tag name="sulu_content.default_main_webspace" value="example" />`
        //      on the template itself which will be injected with ["type" => ["template-key" => "webspace-key"]] into this service.
        if (\array_key_exists('mainWebspace', $data)) {
            $dimensionContent->setMainWebspace($data['mainWebspace']);
        }

        if (!$dimensionContent->getMainWebspace()) {
            // if no main webspace is yet set a default webspace will be set
            $dimensionContent->setMainWebspace($this->getDefaultWebspaceKey());
        }
    }

    private function getDefaultWebspaceKey(): ?string
    {
        if (!$this->defaultWebspaceKey) {
            $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
            $webspace = \reset($webspaces);

            if ($webspace) {
                $this->defaultWebspaceKey = $webspace->getKey();
            }
        }

        return $this->defaultWebspaceKey;
    }
}
