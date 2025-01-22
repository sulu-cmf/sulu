<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle;

use Sulu\Bundle\TestBundle\DependencyInjection\Compiler\ReplaceTestClientPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class SuluTestBundle extends Bundle
{
    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * @internal
     */
    public static function getConfigDir(): string
    {
        return __DIR__ . '/Resources/app/config';
    }
}
