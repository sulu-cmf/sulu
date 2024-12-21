<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface as SymfonyContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

if (\class_exists(SymfonyContextAwareNormalizerInterface::class)) {
    // BC Layer for Symfony <= 6.4: https://github.com/symfony/symfony/blob/7.1/UPGRADE-7.0.md#serializer
    interface ContextAwareNormalizerInterface extends SymfonyContextAwareNormalizerInterface
    {
    }
} else {
    interface ContextAwareNormalizerInterface extends NormalizerInterface
    {
    }
}
