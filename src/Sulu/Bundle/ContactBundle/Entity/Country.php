<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Intl\Countries;

#[ExclusionPolicy('all')]
class Country
{
    /**
     * @var string
     */
    protected $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    #[VirtualProperty]
    #[SerializedName('code')]
    public function getCode(): string
    {
        return $this->code;
    }

    #[VirtualProperty]
    #[SerializedName('name')]
    public function getName(?string $displayLocale = null): string
    {
        return Countries::getName($this->code, $displayLocale);
    }
}
