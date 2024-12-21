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
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface as CategoryEntity;
use Sulu\Bundle\ContactBundle\Entity\BankAccount as BankAccountEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactAddress as ContactAddressEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface as ContactEntity;
use Sulu\Bundle\ContactBundle\Entity\ContactLocale as ContactLocaleEntity;
use Sulu\Bundle\ContactBundle\Entity\Email as EmailEntity;
use Sulu\Bundle\ContactBundle\Entity\Fax as FaxEntity;
use Sulu\Bundle\ContactBundle\Entity\Note as NoteEntity;
use Sulu\Bundle\ContactBundle\Entity\Phone as PhoneEntity;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfile as SocialMediaProfileEntity;
use Sulu\Bundle\ContactBundle\Entity\Url as UrlEntity;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * The Contact class which will be exported to the API.
 */
#[ExclusionPolicy('all')]
class Contact extends ApiWrapper
{
    public const TYPE = 'contact';

    /**
     * @var Media
     */
    private $avatar = null;

    /**
     * @param string $locale The locale of this product
     */
    public function __construct(ContactEntity $contact, $locale)
    {
        $this->entity = $contact;
        $this->locale = $locale;
    }

    /**
     * Get id.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('id')]
    #[Groups(['fullContact', 'partialContact', 'select'])]
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Set first name.
     *
     * @param string $firstName
     *
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->entity->setFirstName($firstName);

        return $this;
    }

    /**
     * Get first name.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('firstName')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getFirstName()
    {
        return $this->entity->getFirstName();
    }

    /**
     * Set middle name.
     *
     * @param string $middleName
     *
     * @return Contact
     */
    public function setMiddleName($middleName)
    {
        $this->entity->setMiddleName($middleName);

        return $this;
    }

    /**
     * Get middle name.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('middleName')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMiddleName()
    {
        return $this->entity->getMiddleName();
    }

    /**
     * Set last name.
     *
     * @param string $lastName
     *
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->entity->setLastName($lastName);

        return $this;
    }

    /**
     * Get last name.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('lastName')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getLastName()
    {
        return $this->entity->getLastName();
    }

    /**
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('fullName')]
    #[Groups(['fullContact', 'partialContact', 'select'])]
    public function getFullName()
    {
        return $this->entity->getFullName();
    }

    /**
     * Set title.
     *
     * @param object $title
     *
     * @return Contact
     */
    public function setTitle($title)
    {
        $this->entity->setTitle($title);

        return $this;
    }

    /**
     * Get title.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('title')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getTitle()
    {
        $title = $this->entity->getTitle();

        if (!$title) {
            return null;
        }

        return $title->getId();
    }

    /**
     * Get name of title.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('titleName')]
    #[Groups(['fullContact'])]
    public function getTitleName()
    {
        $title = $this->entity->getTitle();

        if (!$title) {
            return null;
        }

        return $title->getTitle();
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return Contact
     */
    public function setPosition($position)
    {
        $this->entity->setPosition($position);

        return $this;
    }

    /**
     * Sets current position.
     *
     * @param Position $position
     */
    public function setCurrentPosition($position)
    {
        $this->entity->setPosition($position);
    }

    /**
     * Get position.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('position')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getPosition()
    {
        $position = $this->entity->getPosition();

        if (!$position) {
            return null;
        }

        return $position->getId();
    }

    /**
     * Get name of position.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('positionName')]
    #[Groups(['fullContact'])]
    public function getPositionName()
    {
        $position = $this->entity->getPosition();

        if (!$position) {
            return null;
        }

        return $position->getPosition();
    }

    /**
     * Set birthday.
     *
     * @param \DateTime $birthday
     *
     * @return Contact
     */
    public function setBirthday($birthday)
    {
        $this->entity->setBirthday($birthday);

        return $this;
    }

    /**
     * Get birthday.
     *
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('birthday')]
    #[Groups(['fullContact'])]
    public function getBirthday()
    {
        return $this->entity->getBirthday();
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    #[VirtualProperty]
    #[SerializedName('created')]
    #[Groups(['fullContact'])]
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
    #[Groups(['fullContact'])]
    public function getChanged()
    {
        return $this->entity->getChanged();
    }

    /**
     * Add locale.
     *
     * @return Contact
     */
    public function addLocale(ContactLocaleEntity $locale)
    {
        $this->entity->addLocale($locale);

        return $this;
    }

    /**
     * Remove locale.
     *
     * @return Contact
     */
    public function removeLocale(ContactLocaleEntity $locale)
    {
        $this->entity->removeLocale($locale);

        return $this;
    }

    /**
     * Get locales.
     *
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('locales')]
    #[Groups(['fullContact'])]
    public function getLocales()
    {
        $entities = [];
        if ($this->entity->getLocales()) {
            foreach ($this->entity->getLocales() as $locale) {
                $entities[] = new ContactLocale($locale);
            }
        }

        return $entities;
    }

    /**
     * Set changer.
     *
     * @return Contact
     */
    public function setChanger(?UserInterface $changer = null)
    {
        $this->entity->setChanger($changer);

        return $this;
    }

    /**
     * Set creator.
     *
     * @return Contact
     */
    public function setCreator(?UserInterface $creator = null)
    {
        $this->entity->setCreator($creator);

        return $this;
    }

    public function setNote(?string $note)
    {
        return $this->entity->setNote($note);
    }

    #[VirtualProperty]
    #[SerializedName('note')]
    #[Groups(['fullContact'])]
    public function getNote(): ?string
    {
        return $this->entity->getNote();
    }

    /**
     * Add note.
     *
     * @return Contact
     */
    public function addNote(NoteEntity $note)
    {
        $this->entity->addNote($note);

        return $this;
    }

    /**
     * Remove note.
     *
     * @return $this
     */
    public function removeNote(NoteEntity $note)
    {
        $this->entity->removeNote($note);

        return $this;
    }

    /**
     * Get notes.
     *
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('notes')]
    #[Groups(['fullContact'])]
    public function getNotes()
    {
        $entities = [];
        if ($this->entity->getNotes()) {
            foreach ($this->entity->getNotes() as $note) {
                $entities[] = $note;
            }
        }

        return $entities;
    }

    #[VirtualProperty]
    #[SerializedName('contactDetails')]
    #[Groups(['fullContact'])]
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
     * Add email.
     *
     * @return Contact
     */
    public function addEmail(EmailEntity $email)
    {
        $this->entity->addEmail($email);

        return $this;
    }

    /**
     * Remove email.
     */
    public function removeEmail(EmailEntity $email)
    {
        $this->entity->removeEmail($email);
    }

    /**
     * Get emails.
     *
     * @return array
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

    /**
     * Add phone.
     *
     * @return Contact
     */
    public function addPhone(PhoneEntity $phone)
    {
        $this->entity->addPhone($phone);

        return $this;
    }

    /**
     * Remove phone.
     */
    public function removePhone(PhoneEntity $phone)
    {
        $this->entity->removePhone($phone);
    }

    /**
     * Get phones.
     *
     * @return array
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
     * Add fax.
     *
     * @return Contact
     */
    public function addFax(FaxEntity $fax)
    {
        $this->entity->addFax($fax);

        return $this;
    }

    /**
     * Remove fax.
     */
    public function removeFax(FaxEntity $fax)
    {
        $this->entity->removeFax($fax);
    }

    /**
     * Get faxes.
     *
     * @return array
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
     * @return Contact
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
     * Add url.
     *
     * @return Contact
     */
    public function addUrl(UrlEntity $url)
    {
        $this->entity->addUrl($url);

        return $this;
    }

    /**
     * Remove url.
     */
    public function removeUrl(UrlEntity $url)
    {
        $this->entity->removeUrl($url);
    }

    /**
     * Get urls.
     *
     * @return array
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
     * Set form of address.
     *
     * @param int $formOfAddress
     *
     * @return Contact
     */
    public function setFormOfAddress($formOfAddress)
    {
        $this->entity->setFormOfAddress($formOfAddress);

        return $this;
    }

    /**
     * Get form of address.
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('formOfAddress')]
    #[Groups(['fullContact'])]
    public function getFormOfAddress()
    {
        return $this->entity->getFormOfAddress();
    }

    /**
     * Set salutation.
     *
     * @param string $salutation
     *
     * @return Contact
     */
    public function setSalutation($salutation)
    {
        $this->entity->setSalutation($salutation);

        return $this;
    }

    /**
     * Get salutation.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('salutation')]
    #[Groups(['fullContact'])]
    public function getSalutation()
    {
        return $this->entity->getSalutation();
    }

    /**
     * Sets the avatar (media-api object).
     */
    public function setAvatar(Media $avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * Get the contacts avatar and return the array of different formats.
     *
     * @return Media
     */
    #[VirtualProperty]
    #[SerializedName('avatar')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getAvatar()
    {
        if ($this->avatar) {
            return [
                'id' => $this->avatar->getId(),
                'url' => $this->avatar->getUrl(),
                'thumbnails' => $this->avatar->getFormats(),
            ];
        }

        return;
    }

    /**
     * Add tag.
     *
     * @return Contact
     */
    public function addTag(TagInterface $tag)
    {
        $this->entity->addTag($tag);

        return $this;
    }

    /**
     * Remove tag.
     */
    public function removeTag(TagInterface $tag)
    {
        $this->entity->removeTag($tag);
    }

    /**
     * Get tags.
     *
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('tags')]
    #[Groups(['fullContact'])]
    public function getTags()
    {
        return $this->entity->getTagNameArray();
    }

    /**
     * Get bank accounts.
     *
     * @return array
     */
    #[VirtualProperty]
    #[SerializedName('bankAccounts')]
    #[Groups(['fullContact'])]
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
     * Set newsletter.
     *
     * @param bool $newsletter
     *
     * @return Contact
     */
    public function setNewsletter($newsletter)
    {
        $this->entity->setNewsletter($newsletter);

        return $this;
    }

    /**
     * Get newsletter.
     *
     * @return bool
     */
    #[VirtualProperty]
    #[SerializedName('newsletter')]
    #[Groups(['fullContact'])]
    public function getNewsletter()
    {
        return $this->entity->getNewsletter();
    }

    /**
     * Set gender.
     *
     * @param string $gender
     *
     * @return Contact
     */
    public function setGender($gender)
    {
        $this->entity->setGender($gender);

        return $this;
    }

    /**
     * Get gender.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('gender')]
    #[Groups(['fullContact'])]
    public function getGender()
    {
        return $this->entity->getGender();
    }

    /**
     * Returns main account.
     */
    #[VirtualProperty]
    #[SerializedName('account')]
    #[Groups(['fullContact'])]
    public function getAccount()
    {
        $mainAccount = $this->entity->getMainAccount();
        if (!\is_null($mainAccount)) {
            return new Account($mainAccount, $this->locale);
        }

        return;
    }

    /**
     * Returns main addresses.
     */
    #[VirtualProperty]
    #[SerializedName('addresses')]
    #[Groups(['fullContact'])]
    public function getAddresses()
    {
        $contactAddresses = $this->entity->getContactAddresses();
        $addresses = [];

        if (!\is_null($contactAddresses)) {
            /** @var ContactAddressEntity $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                $address = $contactAddress->getAddress();
                $address->setPrimaryAddress($contactAddress->getMain());
                $addresses[] = new Address($address, $this->locale);
            }
        }

        return $addresses;
    }

    /**
     * Set main email.
     *
     * @param string $mainEmail
     *
     * @return Contact
     */
    public function setMainEmail($mainEmail)
    {
        $this->entity->setMainEmail($mainEmail);

        return $this;
    }

    /**
     * Get main email.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainEmail')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMainEmail()
    {
        return $this->entity->getMainEmail();
    }

    /**
     * Set main phone.
     *
     * @param string $mainPhone
     *
     * @return Contact
     */
    public function setMainPhone($mainPhone)
    {
        $this->entity->setMainPhone($mainPhone);

        return $this;
    }

    /**
     * Get main phone.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainPhone')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMainPhone()
    {
        return $this->entity->getMainPhone();
    }

    /**
     * Set main fax.
     *
     * @param string $mainFax
     *
     * @return Contact
     */
    public function setMainFax($mainFax)
    {
        $this->entity->setMainFax($mainFax);

        return $this;
    }

    /**
     * Get main fax.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainFax')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMainFax()
    {
        return $this->entity->getMainFax();
    }

    /**
     * Set main url.
     *
     * @param string $mainUrl
     *
     * @return Contact
     */
    public function setMainUrl($mainUrl)
    {
        $this->entity->setMainUrl($mainUrl);

        return $this;
    }

    /**
     * Get main url.
     *
     * @return string
     */
    #[VirtualProperty]
    #[SerializedName('mainUrl')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMainUrl()
    {
        return $this->entity->getMainUrl();
    }

    /**
     * Returns the main address.
     */
    #[VirtualProperty]
    #[SerializedName('mainAddress')]
    #[Groups(['fullContact', 'partialContact'])]
    public function getMainAddress()
    {
        $contactAddresses = $this->entity->getContactAddresses();

        if (!\is_null($contactAddresses)) {
            /** @var ContactAddressEntity $contactAddress */
            foreach ($contactAddresses as $contactAddress) {
                if ((bool) $contactAddress->getMain()) {
                    return $contactAddress->getAddress();
                }
            }
        }

        return;
    }

    /**
     * Add media.
     *
     * @return Contact
     */
    public function addMedia(MediaInterface $media)
    {
        $this->entity->addMedia($media);

        return $this;
    }

    /**
     * Remove media.
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
    #[Groups(['fullContact'])]
    public function getMedias()
    {
        $entities = [];
        if ($this->entity->getMedias()) {
            foreach ($this->entity->getMedias() as $media) {
                $entities[] = $media->getId();
            }
        }

        return $entities;
    }

    /**
     * Add category.
     *
     * @return Contact
     */
    public function addCategory(CategoryEntity $category)
    {
        $this->entity->addCategory($category);

        return $this;
    }

    /**
     * Remove category.
     */
    public function removeCategory(CategoryEntity $category)
    {
        $this->entity->removeCategory($category);
    }

    /**
     * Get categories.
     *
     * @return Category[]
     */
    #[VirtualProperty]
    #[SerializedName('categories')]
    #[Groups(['fullContact'])]
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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getLastName(),
            'firstName' => $this->getFirstName(),
            'middleName' => $this->getMiddleName(),
            'lastName' => $this->getLastName(),
            'title' => $this->getTitle(),
            'position' => $this->getPosition(),
            'birthday' => $this->getBirthday(),
            'created' => $this->getCreated(),
            'changed' => $this->getChanged(),
        ];
    }
}
