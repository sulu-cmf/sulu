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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Structure;

use Sulu\Component\Content\Compat\RoutableStructureInterface as SuluRoutableStructureInterface;

if (\interface_exists(SuluRoutableStructureInterface::class)) {
    /**
     * @deprecated will be removed, as soon as the ArticleBundle rises the minimum requirement of sulu to a version,
     * where this interface exists
     *
     * @internal
     */
    interface RoutableStructureInterface extends SuluRoutableStructureInterface
    {
    }
} else {
    /**
     * @deprecated will be removed, as soon as the ArticleBundle rises the minimum requirement of sulu to a version,
     * where this interface exists
     *
     * @internal
     */
    interface RoutableStructureInterface
    {
        /**
         * twig template of template definition.
         *
         * @return string
         */
        public function getView();

        /**
         * controller which renders the twig template.
         *
         * @return string
         */
        public function getController();

        /**
         * cacheLifeTime of template definition.
         *
         * @return array{
         *     type: string,
         *     value: string,
         * }
         */
        public function getCacheLifeTime();
    }
}
