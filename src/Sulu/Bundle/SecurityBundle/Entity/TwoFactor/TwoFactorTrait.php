<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity\TwoFactor;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

/**
 * @internal
 */
trait TwoFactorTrait
{
    use BackupCodeTrait;
    use EmailTrait;
    use GoogleTrait;
    use PreferredProviderTrait;
    use TotpTrait;
    use TrustedDeviceTrait;

    /**
     * @var UserTwoFactor|null
     */
    #[Expose]
    #[Groups(['profile'])]
    protected $twoFactor = null;

    public function getTwoFactor(): ?UserTwoFactor
    {
        return $this->twoFactor;
    }

    public function setTwoFactor(?UserTwoFactor $twoFactor)
    {
        $this->twoFactor = $twoFactor;

        return $this;
    }

    /**
     * @internal
     */
    protected function setTwoFactorOption(string $name, ?string $value): void
    {
        $twoFactor = $this->getTwoFactor();

        if (!$twoFactor) {
            throw new \LogicException(
                \sprintf(
                    'The method "%s::%s" should not be called without twoFactor being set.',
                    __CLASS__,
                    __METHOD__
                )
            );
        }

        $options = $twoFactor->getOptions() ?: [];
        $options[$name] = $value;

        $twoFactor->setOptions($options);
    }
}
