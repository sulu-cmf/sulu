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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress as AccountAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountContact as AccountContactEntity;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\Contact as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile as SocialMediaProfileEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The Account class which will be exported to the API.
 */
#[ExclusionPolicy('all')]
class Account extends ApiWrapper
{
    public const TYPE = 'account';

    /**
     * @var Media
     */
    private $logo = null;

    /**
     * @param string $locale The locale of this product
     */
    public function __construct(AccountInterface $account, $locale)
    {
        $this->entity = $account;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the product.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Account
     */
    public function setLft($lft)
    {
        $this->entity->setLft($lft);

        return $this;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return Account
     */
    public function setRgt($rgt)
    {
        $this->entity->setRgt($rgt);

        return $this;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Account
     */
    public function setDepth($depth)
    {
        $this->entity->setDepth($depth);

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('depth')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getDepth()
    {
        return $this->entity->getDepth();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Account
     */
    public function setName($name)
    {
        $this->entity->setName($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('name')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getName()
    {
        return $this->entity->getName();
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('created')]
    #[Groups(['fullAccount'])]
    public function getCreated()
    {
        return $this->entity->getCreated();
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('changed')]
    #[Groups(['fullAccount'])]
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Set parent.
     *
     * @return Account
     */
    public function setParent(?AccountInterface $parent = null)
    {
        $this->entity->setParent($parent);

        return $this;
    }

    /**
     * Get parent.
     *
     * @return AccountInterface
     */
    #[VirtualProperty]
    #[SerializedName('parent')]
    #[Groups(['fullAccount'])]
    public function getParent()
    {
        $account = $this->entity->getParent();
        if ($account) {
            return new self($account, $this->locale);
        }

        return;
    }

    /**
     * Add urls.
     *
     * @return Account
     */
    public function addUrl(UrlEntity $url)
    {
        $this->entity->addUrl($url);

        return $this;
    }

    /**
     * Remove urls.
     */
    public function removeUrl(UrlEntity $url)
    {
        $this->entity->removeUrl($url);
    }

    /**
     * Get urls.
     *
     * @return UrlEntity[]
     */
    public function getUrls()
    {
        $urls = [];
        if ($this->entity->getUrls()) {
            foreach ($this->entity->getUrls() as $url) {
                $urls[] = new Url($url, $this->locale);
            }
        }

        return $urls;
    }

    /**
     * Add phones.
     *
     * @return Account
     */
    public function addPhone(PhoneEntity $phones)
    {
        $this->entity->addPhone($phones);

        return $this;
    }

    /**
     * Remove phones.
     */
    public function removePhone(PhoneEntity $phone)
    {
        $this->entity->removePhone($phone);
    }

    /**
     * Get phones.
     *
     * @return PhoneEntity[]
     */
    public function getPhones()
    {
        $phones = [];
        if ($this->entity->getPhones()) {
            foreach ($this->entity->getPhones() as $phone) {
                $phones[] = new Phone($phone, $this->locale);
            }
        }

        return $phones;
    }

    /**
     * Add emails.
     *
     * @return Account
     */
    public function addEmail(EmailEntity $email)
    {
        $this->entity->addEmail($email);

        return $this;
    }

    /**
     * Remove emails.
     */
    public function removeEmail(EmailEntity $email)
    {
        $this->entity->removeEmail($email);
    }

    /**
     * Get emails.
     *
     * @return EmailEntity[]
     */
    public function getEmails()
    {
        $emails = [];
        if ($this->entity->getEmails()) {
            foreach ($this->entity->getEmails() as $email) {
                $emails[] = new Email($email, $this->locale);
            }
        }

        return $emails;
    }

    public function setNote(?string $note)
    {
        return $this->entity->setNote($note);
    }

    #[VirtualProperty]
    #[SerializedName('note')]
    #[Groups(['fullAccount'])]
    public function getNote(): ?string
    {
        return $this->entity->getNote();
    }

    /**
     * Add notes.
     *
     * @return Account
     */
    public function addNote(NoteEntity $notes)
    {
        $this->entity->addNote($notes);

        return $this;
    }

    /**
     * Remove notes.
     */
    public function removeNote(NoteEntity $note)
    {
        $this->entity->removeNote($note);
    }

    /**
     * Get notes.
     *
     * @return NoteEntity[]
     */
    #[VirtualProperty]
    #[SerializedName('notes')]
    #[Groups(['fullAccount'])]
    public function getNotes()
    {
        $notes = [];
        if ($this->entity->getNotes()) {
            foreach ($this->entity->getNotes() as $note) {
                $notes[] = $note;
            }
        }

        return $notes;
    }

    #[VirtualProperty]
    #[SerializedName('contactDetails')]
    #[Groups(['fullAccount'])]
    public function getContactDetails()
    {
        return [
            'emails' => $this->getEmails(),
            'faxes' => $this->getFaxes(),
            'phones' => $this->getPhones(),
            'socialMedia' => $this->getSocialMediaProfiles(),
            'websites' => $this->getUrls(),
        ];
    }

    /**
     * Add faxes.
     *
     * @return Account
     */
    public function addFax(FaxEntity $fax)
    {
        $this->entity->addFax($fax);

        return $this;
    }

    /**
     * Remove faxes.
     */
    public function removeFax(FaxEntity $fax)
    {
        $this->entity->removeFax($fax);
    }

    /**
     * Get faxes.
     *
     * @return FaxEntity[]
     */
    public function getFaxes()
    {
        $faxes = [];
        if ($this->entity->getFaxes()) {
            foreach ($this->entity->getFaxes() as $fax) {
                $faxes[] = new Fax($fax, $this->locale);
            }
        }

        return $faxes;
    }

    /**
     * Add social media profile.
     *
     * @return Account
     */
    public function addSocialMediaProfile(SocialMediaProfileEntity $socialMediaProfile)
    {
        $this->entity->addSocialMediaProfile($socialMediaProfile);

        return $this;
    }

    /**
     * Remove social media profile.
     */
    public function removeSocialMediaProfile(SocialMediaProfileEntity $socialMediaProfile)
    {
        $this->entity->removeSocialMediaProfile($socialMediaProfile);
    }

    /**
     * Get social media profiles.
     *
     * @return SocialMediaProfileEntity[]
     */
    public function getSocialMediaProfiles()
    {
        $socialMediaProfiles = [];
        if ($this->entity->getSocialMediaProfiles()) {
            foreach ($this->entity->getSocialMediaProfiles() as $socialMediaProfile) {
                $socialMediaProfiles[] = new SocialMediaProfile($socialMediaProfile, $this->locale);
            }
        }

        return $socialMediaProfiles;
    }

    /**
     * Set corporation.
     *
     * @param string $corporation
     *
     * @return Account
     */
    public function setCorporation($corporation)
    {
        $this->entity->setCorporation($corporation);

        return $this;
    }

    /**
     * Get corporation.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('corporation')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getCorporation()
    {
        return $this->entity->getCorporation();
    }

    /**
     * Set uid.
     *
     * @param string $uid
     *
     * @return Account
     */
    public function setUid($uid)
    {
        $this->entity->setUid($uid);

        return $this;
    }

    /**
     * Get uid.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('uid')]
    #[Groups(['fullAccount'])]
    public function getUid()
    {
        return $this->entity->getUid();
    }

    /**
     * Set registerNumber.
     *
     * @param string $registerNumber
     *
     * @return Account
     */
    public function setRegisterNumber($registerNumber)
    {
        $this->entity->setRegisterNumber($registerNumber);

        return $this;
    }

    /**
     * Get registerNumber.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('registerNumber')]
    #[Groups(['fullAccount'])]
    public function getRegisterNumber()
    {
        return $this->entity->getRegisterNumber();
    }

    /**
     * Add bankAccounts.
     *
     * @return Account
     */
    public function addBankAccount(BankAccountEntity $bankAccount)
    {
        $this->entity->addBankAccount($bankAccount);

        return $this;
    }

    /**
     * Remove bankAccounts.
     */
    public function removeBankAccount(BankAccountEntity $bankAccount)
    {
        $this->entity->removeBankAccount($bankAccount);
    }

    /**
     * Get bankAccounts.
     *
     * @return BankAccountEntity[]
     */
    #[VirtualProperty]
    #[SerializedName('bankAccounts')]
    #[Groups(['fullAccount'])]
    public function getBankAccounts()
    {
        $bankAccounts = [];
        if ($this->entity->getBankAccounts()) {
            foreach ($this->entity->getBankAccounts() as $bankAccount) {
                /* @var BankAccountEntity $bankAccount */
                $bankAccounts[] = new BankAccount($bankAccount);
            }
        }

        return $bankAccounts;
    }

    /**
     * Add tags.
     *
     * @return Account
     */
    public function addTag(TagInterface $tag)
    {
        $this->entity->addTag($tag);

        return $this;
    }

    /**
     * Remove tags.
     */
    public function removeTag(TagInterface $tag)
    {
        $this->entity->removeTag($tag);
    }

    /**
     * Get tags.
     *
     * @return TagInterface[]
     */
    #[VirtualProperty]
    #[SerializedName('tags')]
    #[Groups(['fullAccount'])]
    public function getTags()
    {
        return $this->entity->getTagNameArray();
    }

    /**
     * Add accountContacts.
     *
     * @return Account
     */
    public function addAccountContact(AccountContactEntity $accountContact)
    {
        $this->entity->addAccountContact($accountContact);

        return $this;
    }

    /**
     * Remove accountContacts.
     */
    public function removeAccountContact(AccountContactEntity $accountContact)
    {
        $this->entity->removeAccountContact($accountContact);
    }

    /**
     * Get accountContacts.
     *
     * @return AccountContact[]
     */
    #[VirtualProperty]
    #[SerializedName('accountContacts')]
    #[Groups(['fullAccount'])]
    public function getAccountContacts()
    {
        $accountContacts = [];
        if ($this->entity->getAccountContacts()) {
            foreach ($this->entity->getAccountContacts() as $AccountContact) {
                $accountContacts[] = new AccountContact($AccountContact, $this->locale);
            }
        }

        return $accountContacts;
    }

    /**
     * Set placeOfJurisdiction.
     *
     * @param string $placeOfJurisdiction
     *
     * @return Account
     */
    public function setPlaceOfJurisdiction($placeOfJurisdiction)
    {
        $this->entity->setPlaceOfJurisdiction($placeOfJurisdiction);

        return $this;
    }

    /**
     * Get placeOfJurisdiction.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('placeOfJurisdiction')]
    #[Groups(['fullAccount'])]
    public function getPlaceOfJurisdiction()
    {
        return $this->entity->getPlaceOfJurisdiction();
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return Account
     */
    public function setNumber($number)
    {
        $this->entity->setNumber($number);

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('number')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getNumber()
    {
        return $this->entity->getNumber();
    }

    /**
     * Set externalId.
     *
     * @param string $externalId
     *
     * @return Account
     */
    public function setExternalId($externalId)
    {
        $this->entity->setExternalId($externalId);

        return $this;
    }

    /**
     * Get externalId.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('externalId')]
    #[Groups(['fullAccount'])]
    public function getExternalId()
    {
        return $this->entity->GetExternalId();
    }

    /**
     * Set mainContact.
     *
     * @return Account
     */
    public function setMainContact(?ContactEntity $mainContact = null)
    {
        $this->entity->setMainContact($mainContact);

        return $this;
    }

    /**
     * Get mainContact.
     *
     * @return Account
     */
    #[VirtualProperty]
    #[SerializedName('mainContact')]
    #[Groups(['fullAccount'])]
    public function getMainContact()
    {
        if ($this->entity->getMainContact()) {
            return new Contact($this->entity->getMainContact(), $this->locale);
        }

        return null;
    }

    /**
     * Set mainEmail.
     *
     * @param string $mainEmail
     *
     * @return Account
     */
    public function setMainEmail($mainEmail)
    {
        $this->entity->setMainEmail($mainEmail);

        return $this;
    }

    /**
     * Get mainEmail.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainEmail')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getMainEmail()
    {
        return $this->entity->getMainEmail();
    }

    /**
     * Set mainPhone.
     *
     * @param string $mainPhone
     *
     * @return Account
     */
    public function setMainPhone($mainPhone)
    {
        $this->entity->setMainPhone($mainPhone);

        return $this;
    }

    /**
     * Get mainPhone.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainPhone')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getMainPhone()
    {
        return $this->entity->getMainPhone();
    }

    /**
     * Set mainFax.
     *
     * @param string $mainFax
     *
     * @return Account
     */
    public function setMainFax($mainFax)
    {
        $this->entity->setMainFax($mainFax);

        return $this;
    }

    /**
     * Get mainFax.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainFax')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set mainUrl.
     *
     * @param string $mainUrl
     *
     * @return Account
     */
    public function setMainUrl($mainUrl)
    {
        $this->entity->setMainUrl($mainUrl);

        return $this;
    }

    /**
     * Get mainUrl.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainUrl')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getMainUrl()
    {
        return $this->entity->getMainUrl();
    }

    /**
     * Add accountAddresses.
     *
     * @return Account
     */
    public function addAccountAddress(AccountAddressEntity $accountAddress)
    {
        $this->entity->addAccountAddress($accountAddress);

        return $this;
    }

    /**
     * Remove accountAddresses.
     */
    public function removeAccountAddress(AccountAddressEntity $accountAddresses)
    {
        $this->entity->removeAccountAddress($accountAddresses);
    }

    /**
     * Get accountAddresses.
     *
     * @return AccountAddress[]
     */
    #[VirtualProperty]
    #[SerializedName('accountAddresses')]
    public function getAccountAddresses()
    {
        $accountAddresses = [];
        if ($this->entity->getAccountAddresses()) {
            foreach ($this->entity->getAccountAddresses() as $adr) {
                $accountAddress[] = new AccountAddress($adr);
            }
        }

        return $accountAddresses;
    }

    /**
     * returns addresses.
     */
    #[VirtualProperty]
    #[SerializedName('addresses')]
    #[Groups(['fullAccount'])]
    public function getAddresses()
    {
        $accountAddresses = $this->entity->getAccountAddresses();
        $addresses = [];

        if (!\is_null($accountAddresses)) {
            /** @var ContactAddress $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                $address = $accountAddress->getAddress();
                $address->setPrimaryAddress($accountAddress->getMain());
                $addresses[] = new Address($address, $this->locale);
            }
        }

        return $addresses;
    }

    /**
     * Returns the main address.
     */
    #[VirtualProperty]
    #[SerializedName('mainAddress')]
    #[Groups(['fullAccount', 'partialAccount'])]
    public function getMainAddress()
    {
        $accountAddresses = $this->entity->getAccountAddresses();

        if (!\is_null($accountAddresses)) {
            /** @var AccountAddressEntity $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                if ($accountAddress->getMain()) {
                    return $accountAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * Get contacts.
     *
     * @return Contact[]
     */
    #[VirtualProperty]
    #[SerializedName('contacts')]
    #[Groups(['fullAccount'])]
    public function getContacts()
    {
        $accountContacts = $this->entity->getAccountContacts();
        $contacts = [];

        if (!\is_null($accountContacts)) {
            /** @var AccountContactEntity $accountContact */
            foreach ($accountContacts as $accountContact) {
                $contacts[] = new Contact($accountContact->getContact(), $this->locale);
            }
        }

        return $contacts;
    }

    /**
     * Sets the logo (media-api object).
     */
    public function setLogo(Media $logo)
    {
        $this->logo = $logo;
    }

    /**
     * Get the accounts logo and return the array of different formats.
     *
     * @return Media
     */
    #[VirtualProperty]
    #[SerializedName('logo')]
    #[Groups(['fullAccount'])]
    public function getLogo()
    {
        if ($this->logo) {
            return [
                'id' => $this->logo->getId(),
                'url' => $this->logo->getUrl(),
                'thumbnails' => $this->logo->getFormats(),
            ];
        }

        return;
    }

    /**
     * Add media.
     *
     * @return Account
     */
    public function addMedia(MediaInterface $media)
    {
        $this->entity->addMedia($media);

        return $this;
    }

    /**
     * Remove medias.
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->entity->removeMedia($media);
    }

    /**
     * Get medias.
     *
     * @return Media[]
     */
    #[VirtualProperty]
    #[SerializedName('medias')]
    #[Groups(['fullAccount'])]
    public function getMedias()
    {
        $medias = [];
        if ($this->entity->getMedias()) {
            foreach ($this->entity->getMedias() as $media) {
                $medias[] = $media->getId();
            }
        }

        return $medias;
    }

    /**
     * Get categories.
     *
     * @return Category[]
     */
    #[VirtualProperty]
    #[SerializedName('categories')]
    #[Groups(['fullAccount'])]
    public function getCategories()
    {
        return \array_map(function($category) {
            return $category->getId();
        }, $this->entity->getCategories()->toArray());
    }

    /**
     * Get type of api entity.
     *
     * @return string
     */
    #[VirtualProperty]
    public function getType()
    {
        return self::TYPE;
    }
}
