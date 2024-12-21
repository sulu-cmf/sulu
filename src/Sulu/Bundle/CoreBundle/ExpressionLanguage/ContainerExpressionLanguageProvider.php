<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\ExpressionLanguage;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ContainerExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(private ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'service',
                function() {
                },
                function(array $variables, $value) {
                    return $this->container->get($value);
                }
            ),

            new ExpressionFunction(
                'parameter',
                function() {
                },
                function(array $variables, $value) {
                    return $this->container->getParameter($value);
                }
            ),
        ];
    }
}
