<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebsiteAdmin extends Admin
{
    /**
     * Returns security context for analytics in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public static function getAnalyticsSecurityContext($webspaceKey)
    {
        return sprintf('%s%s.%s', ContentAdmin::SECURITY_SETTINGS_CONTEXT_PREFIX, $webspaceKey, 'analytics');
    }

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        /* @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $webspaceContexts[self::getAnalyticsSecurityContext($webspace->getKey())] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
            ];
        }

        return [
            'Sulu' => [
                'Webspace Settings' => $webspaceContexts,
            ],
        ];
    }
}
