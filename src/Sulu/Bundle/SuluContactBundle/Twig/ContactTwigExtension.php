<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Twig;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;

/**
 * Extension to handle contacts in frontend
 */
class ContactTwigExtension extends \Twig_Extension
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Cache
     */
    private $cache;

    function __construct(Cache $cache, UserRepository $userRepository)
    {
        $this->cache = $cache;
        $this->userRepository = $userRepository;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('resolve_user', array($this, 'resolveUserFunction'))
        );
    }

    /**
     * resolves user id to user data
     * @param integer $userId id to resolve
     * @return Contact
     */
    public function resolveUserFunction($userId)
    {
        if (!$this->cache->contains($userId)) {
            $user = $this->userRepository->findUserById($userId);

            if ($user === null) {
                return null;
            }

            $this->cache->save($userId, $user->getContact());
        }

        return $this->cache->fetch($userId);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sulu_contact';
    }
}
