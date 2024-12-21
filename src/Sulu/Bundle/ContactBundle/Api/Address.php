<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Api;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\Address as AddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AddressType as AddressTypeEntity;
use Sulu\Bundle\ContactBundle\Entity\Country;
use Sulu\Component\Rest\ApiWrapper;

class Address extends ApiWrapper
{
    public function __construct(AddressEntity $account, $locale)
    {
        $this->entity = $account;
        $this->locale = $locale;
    }

    public function setStreet(string $street): self
    {
        $this->entity->setStreet($street);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('street')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getStreet(): ?string
    {
        return $this->entity->getStreet();
    }

    public function setNumber(string $number): self
    {
        $this->entity->setNumber($number);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('number')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getNumber(): ?string
    {
        return $this->entity->getNumber();
    }

    public function setAddition(string $addition): self
    {
        $this->entity->setAddition($addition);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('addition')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getAddition(): ?string
    {
        return $this->entity->getAddition();
    }

    public function setZip(string $zip): self
    {
        $this->entity->setZip($zip);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('zip')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getZip(): ?string
    {
        return $this->entity->getZip();
    }

    public function setCity($city): self
    {
        $this->entity->setCity($city);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('city')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getCity(): ?string
    {
        return $this->entity->getCity();
    }

    public function setState(string $state): self
    {
        $this->entity->setState($state);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('state')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getState(): ?string
    {
        return $this->entity->getState();
    }

    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getId(): ?int
    {
        return $this->entity->getId();
    }

    public function setAddressType(AddressTypeEntity $addressType): self
    {
        $this->entity->setAddressType($addressType);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('addressType')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getAddressType(): ?int
    {
        return $this->entity->getAddressType()->getId();
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->entity->setCountryCode($countryCode);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('countryCode')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getCountryCode(): ?string
    {
        return $this->entity->getCountryCode();
    }

    public function getCountry(): ?Country
    {
        return $this->entity->getCountry();
    }

    public function setPrimaryAddress(bool $primaryAddress): self
    {
        $this->entity->setPrimaryAddress($primaryAddress);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('primaryAddress')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPrimaryAddress(): ?bool
    {
        return $this->entity->getPrimaryAddress();
    }

    public function setDeliveryAddress(bool $deliveryAddress): self
    {
        $this->entity->setDeliveryAddress($deliveryAddress);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('deliveryAddress')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getDeliveryAddress(): ?bool
    {
        return $this->entity->getDeliveryAddress();
    }

    public function setBillingAddress(bool $billingAddress)
    {
        $this->entity->setBillingAddress($billingAddress);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('billingAddress')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getBillingAddress(): ?bool
    {
        return $this->entity->getBillingAddress();
    }

    public function setPostboxNumber(string $postboxNumber): self
    {
        $this->entity->setPostboxNumber($postboxNumber);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('postboxNumber')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPostboxNumber(): ?string
    {
        return $this->entity->getPostboxNumber();
    }

    public function setPostboxPostcode(string $postboxPostcode): self
    {
        $this->entity->setPostboxPostcode($postboxPostcode);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('postboxPostcode')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPostboxPostcode(): ?string
    {
        return $this->entity->getPostboxPostcode();
    }

    public function setPostboxCity(string $postboxCity): self
    {
        $this->entity->setPostboxCity($postboxCity);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('postboxCity')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getPostboxCity(): ?string
    {
        return $this->entity->getPostboxCity();
    }

    public function addContactAddress(ContactAddress $contactAddresses): self
    {
        $this->entity->addContactAddress($contactAddresses);

        return $this;
    }

    public function removeContactAddress(ContactAddress $contactAddresses): self
    {
        $this->entity->removeContactAddress($contactAddresses);

        return $this;
    }

    public function getContactAddresses()
    {
        return $this->entity->getContactAddresses();
    }

    public function addAccountAddress(AccountAddress $accountAddresses): self
    {
        $this->entity->addAccountAddress($accountAddresses);

        return $this;
    }

    public function removeAccountAddress(AccountAddress $accountAddresses): self
    {
        $this->entity->removeAccountAddress($accountAddresses);

        return $this;
    }

    public function getAccountAddresses()
    {
        return $this->entity->getAccountAddresses();
    }

    public function setNote(string $note): self
    {
        $this->entity->setNote($note);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('note')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getNote(): ?string
    {
        return $this->entity->getNote();
    }

    public function setTitle(string $title): self
    {
        $this->entity->setTitle($title);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('title')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getTitle(): ?string
    {
        return $this->entity->getTitle();
    }

    #[VirtualProperty]
    #[SerializedName('latitude')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getLatitude(): ?float
    {
        return $this->entity->getLatitude();
    }

    public function setLatitude(float $latitude): self
    {
        $this->entity->setLatitude($latitude);

        return $this;
    }

    #[VirtualProperty]
    #[SerializedName('longitude')]
    #[Groups(['fullContact', 'fullAccount'])]
    public function getLongitude(): ?float
    {
        return $this->entity->getLongitude();
    }

    public function setLongitude(float $longitude): self
    {
        $this->entity->setLongitude($longitude);

        return $this;
    }
}
