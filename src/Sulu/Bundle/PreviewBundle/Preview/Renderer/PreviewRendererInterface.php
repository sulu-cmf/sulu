<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

/**
 * Interface for preview-renderer.
 */
interface PreviewRendererInterface
{
    /**
     * Renders object in given webspace and locale.
     *
     * @param string $id
     * @param bool $partial
     *
     * @return string
     */
    public function render(
        $object,
        $id,
        $partial = false,
        $options = []
    );
}
