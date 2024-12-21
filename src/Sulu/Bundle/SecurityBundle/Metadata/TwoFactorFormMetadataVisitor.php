<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Security as SymfonyCoreSecurity;

/**
 * @internal
 */
class TwoFactorFormMetadataVisitor implements FormMetadataVisitorInterface
{
    /**
     * @param string[] $twoFactorMethods
     */
    public function __construct(
        private array $twoFactorMethods,
        private ?string $twoFactorForcePattern,
        private Security|SymfonyCoreSecurity|null $security,
    ) {
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if ('profile_details' !== $formMetadata->getKey()) {
            return;
        }

        $items = $formMetadata->getItems();
        if (empty($this->twoFactorMethods)) {
            unset($items['twoFactor/method']);
        } else {
            /** @var FieldMetadata $methodMetadata */
            $methodMetadata = $items['twoFactor/method'];
            /** @var array{values: OptionMetadata} $options */
            $options = $methodMetadata->getOptions();
            $values = $options['values'];
            /** @var OptionMetadata[] $methods */
            $methods = $values->getValue();

            foreach ($methods as $key => $value) {
                $name = $value->getName();
                if ($name && !\in_array($name, $this->twoFactorMethods)) {
                    unset($methods[$key]);
                }

                if (!$name && null !== $this->twoFactorForcePattern) {
                    /** @var User|null $user */
                    $user = $this->security?->getUser();

                    if (\preg_match($this->twoFactorForcePattern, $user?->getEmail() ?: '')) {
                        unset($methods[$key]);
                    }
                }
            }

            $values->setValue(\array_values($methods));
        }

        $formMetadata->setItems($items);
        $formMetadata->setCacheable(false);
    }
}
