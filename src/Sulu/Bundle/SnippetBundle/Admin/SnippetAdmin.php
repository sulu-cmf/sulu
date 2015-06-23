<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

/**
 * Admin for snippet.
 */
class SnippetAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;

        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('navigation.webspaces');

        $global = new NavigationItem('navigation.global-content');
        $global->setIcon('globe');
        $section->addChild($global);

        if ($this->securityChecker->hasPermission('sulu.global.snippets', 'view')) {
            $snippet = new NavigationItem('navigation.snippets');
            $snippet->setIcon('bullseye');
            $snippet->setAction('snippet/snippets');
            $global->addChild($snippet);
        }

        if ($global->hasChildren()) {
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulusnippet';
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContexts()
    {
        return array(
            'Sulu' => array(
                'Global' => array(
                    'sulu.global.snippets',
                ),
            ),
        );
    }
}
