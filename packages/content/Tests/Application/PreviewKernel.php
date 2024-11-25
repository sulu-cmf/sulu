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

namespace Sulu\Bundle\ContentBundle\Tests\Application;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewKernel as SuluPreviewKernel;

class PreviewKernel extends SuluPreviewKernel
{
    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
