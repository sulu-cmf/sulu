<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for index.
 */
class IndexBuilder extends SuluBuilder
{
    /**
     * Return the name for this builder.
     *
     * @return string
     */
    public function getName()
    {
        return 'search_index';
    }

    /**
     * Return the dependencies for this builder.
     *
     * @return array
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * Execute the build logic.
     */
    public function build()
    {
        $this->execCommand('Create search indexes', 'sulu:search:reindex-content');
    }
}
