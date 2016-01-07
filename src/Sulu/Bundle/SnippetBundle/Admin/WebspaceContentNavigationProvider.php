<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

/**
 * Provides tabs for webspace settings.
 */
class WebspaceContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * @var bool
     */
    private $defaultEnabled;

    public function __construct($defaultEnabled)
    {
        $this->defaultEnabled = $defaultEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        if ($this->defaultEnabled === false) {
            return [];
        }

        $snippets = new ContentNavigationItem('content-navigation.webspace.snippets');
        $snippets->setId('tab-snippets');
        $snippets->setAction('snippets');
        $snippets->setPosition(25);
        $snippets->setComponent('webspace/settings/snippets@sulusnippet');

        return [$snippets];
    }
}
