<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview\Renderer;

/**
 * Creates new Website-Kernels foreach preview request.
 */
class WebsiteKernelFactory implements KernelFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($environment)
    {
        $kernel = new \WebsiteKernel($environment, $environment === 'dev');
        $kernel->boot();
        $container = $kernel->getContainer();

        if ($container->has('profiler')) {
            $container->get('profiler')->disable();
        }

        return $kernel;
    }
}
