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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

/**
 * Address.
 *
 * @ExclusionPolicy("all");
 */
class Address
{
    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $street;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $number;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $addition;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $zip;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $city;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $state;

    /**
     * @var int
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $id;

    /**
     * @var AddressType
     *
     * @Groups({"fullAccount", "fullContact"})
     * @Expose
     */
    private $addressType;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $countryCode;

    /**
     * @var bool|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $primaryAddress;

    /**
     * @var bool|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $deliveryAddress;

    /**
     * @var bool|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $billingAddress;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxNumber;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxPostcode;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $postboxCity;

    /**
     * @var Collection<int, ContactAddress>
     */
    private $contactAddresses;

    /**
     * @var Collection<int, AccountAddress>
     */
    private $accountAddresses;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $note;

    /**
     * @var string|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $title;

    /**
     * @var float|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $latitude;

    /**
     * @var float|null
     *
     * @Groups({"fullAccount", "partialAccount", "fullContact", "partialContact"})
     * @Expose
     */
    private $longitude;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->accountAddresses = new ArrayCollection();
        $this->contactAddresses = new ArrayCollection();
    }

    /**
     * Set street.
     *
     * @param string|null $street
     *
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street.
     *
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set number.
     *
     * @param string|null $number
     *
     * @return Address
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set addition.
     *
     * @param string|null $addition
     *
     * @return Address
     */
    public function setAddition($addition)
    {
        $this->addition = $addition;

        return $this;
    }

    /**
     * Get addition.
     *
     * @return string|null
     */
    public function getAddition()
    {
        return $this->addition;
    }

    /**
     * Set zip.
     *
     * @param string|null $zip
     *
     * @return Address
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip.
     *
     * @return string|null
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state.
     *
     * @param string|null $state
     *
     * @return Address
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return string|null
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set addressType.
     *
     * @return Address
     */
    public function setAddressType(AddressType $addressType)
    {
        $this->addressType = $addressType;

        return $this;
    }

    /**
     * Get addressType.
     *
     * @return AddressType
     */
    public function getAddressType()
    {
        return $this->addressType;
    }

    /**
     * Set countryCode.
     *
     * @return Address
     */
    public function setCountryCode(?string $countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode.
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function getCountry(): ?Country
    {
        if (!$this->countryCode) {
            return null;
        }

        return new Country($this->countryCode);
    }

    /**
     * Set primaryAddress.
     *
     * @param bool $primaryAddress
     *
     * @return Address
     */
    public function setPrimaryAddress($primaryAddress)
    {
        $this->primaryAddress = $primaryAddress;

        return $this;
    }

    /**
     * Get primaryAddress.
     *
     * @return bool|null
     */
    public function getPrimaryAddress()
    {
        return $this->primaryAddress;
    }

    /**
     * Set deliveryAddress.
     *
     * @param bool $deliveryAddress
     *
     * @return Address
     */
    public function setDeliveryAddress($deliveryAddress)
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    /**
     * Get deliveryAddress.
     *
     * @return bool|null
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddress;
    }

    /**
     * Set billingAddress.
     *
     * @param bool $billingAddress
     *
     * @return Address
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * Get billingAddress.
     *
     * @return bool|null
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * Set postboxNumber.
     *
     * @param string|null $postboxNumber
     *
     * @return Address
     */
    public function setPostboxNumber($postboxNumber)
    {
        $this->postboxNumber = $postboxNumber;

        return $this;
    }

    /**
     * Get postboxNumber.
     *
     * @return string|null
     */
    public function getPostboxNumber()
    {
        return $this->postboxNumber;
    }

    /**
     * Set postboxPostcode.
     *
     * @param string|null $postboxPostcode
     *
     * @return Address
     */
    public function setPostboxPostcode($postboxPostcode)
    {
        $this->postboxPostcode = $postboxPostcode;

        return $this;
    }

    /**
     * Get postboxPostcode.
     *
     * @return string|null
     */
    public function getPostboxPostcode()
    {
        return $this->postboxPostcode;
    }

    /**
     * Set postboxCity.
     *
     * @param string|null $postboxCity
     *
     * @return Address
     */
    public function setPostboxCity($postboxCity)
    {
        $this->postboxCity = $postboxCity;

        return $this;
    }

    /**
     * Get postboxCity.
     *
     * @return string|null
     */
    public function getPostboxCity()
    {
        return $this->postboxCity;
    }

    /**
     * Add contactAddresses.
     *
     * @return Address
     */
    public function addContactAddress(ContactAddress $contactAddresses)
    {
        $this->contactAddresses[] = $contactAddresses;

        return $this;
    }

    /**
     * Remove contactAddresses.
     *
     * @return void
     */
    public function removeContactAddress(ContactAddress $contactAddresses)
    {
        $this->contactAddresses->removeElement($contactAddresses);
    }

    /**
     * Get contactAddresses.
     *
     * @return Collection<int, ContactAddress>
     */
    public function getContactAddresses()
    {
        return $this->contactAddresses;
    }

    /**
     * Add accountAddresses.
     *
     * @return Address
     */
    public function addAccountAddress(AccountAddress $accountAddresses)
    {
        $this->accountAddresses[] = $accountAddresses;

        return $this;
    }

    /**
     * Remove accountAddresses.
     */
    public function removeAccountAddress(AccountAddress $accountAddresses)
    {
        $this->accountAddresses->removeElement($accountAddresses);
    }

    /**
     * Get accountAddresses.
     *
     * @return Collection<int, AccountAddress>
     */
    public function getAccountAddresses()
    {
        return $this->accountAddresses;
    }

    /**
     * returns if address has at least one relation to another entity.
     *
     * @return bool
     */
    public function hasRelations()
    {
        if (!$this->getContactAddresses()->isEmpty()
            || !$this->getAccountAddresses()->isEmpty()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set note.
     *
     * @param string|null $note
     *
     * @return Address
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set title.
     *
     * @param string|null $title
     *
     * @return Address
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns latitude.
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set latitude.
     *
     * @param float|null $latitude
     *
     * @return Address
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Returns longitude.
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set longitude.
     *
     * @param float|null $longitude
     *
     * @return Address
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }
}
