<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;
use Sulu\Component\Security\Authentication\UserInterface;

#[ExclusionPolicy('all')]
class UserTwoFactor
{
    private int $id;

    private UserInterface $user;

    #[Expose]
    #[Groups(['profile'])]
    private ?string $method = null;

    private ?string $options = null;

    public function __construct(TwoFactorInterface $user)
    {
        /** @var UserInterface $user */
        $this->user = $user;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * @return static
     */
    public function setMethod(?string $twoFactorType)
    {
        $this->method = $twoFactorType;

        return $this;
    }

    /**
     * @return array{
     *     backupCodes?: string[],
     *     authCode?: string,
     *     googleAuthenticatorSecret?: string,
     *     totpSecret?: string,
     *     trustedVersion?: int,
     *     googleAuthenticatorUsername?: string,
     *     googleAuthenticatorSecret?: string,
     * }
     */
    public function getOptions(): ?array
    {
        if (null === $this->options) {
            return null;
        }

        /**
         * @var array{
         *     backupCodes?: string[],
         *     authCode?: string,
         *     googleAuthenticatorSecret?: string,
         *     totpSecret?: string,
         *     trustedVersion?: int,
         *     googleAuthenticatorUsername?: string,
         *     googleAuthenticatorSecret?: string,
         * }
         */
        return \json_decode($this->options, true, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param mixed[] $options
     *
     * @return static
     */
    public function setOptions(?array $options)
    {
        $this->options = $options ? \json_encode($options, \JSON_THROW_ON_ERROR) : null;

        return $this;
    }
}
