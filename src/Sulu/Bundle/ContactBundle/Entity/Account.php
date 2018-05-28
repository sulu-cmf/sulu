<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Component\Security\Authentication\UserInterface;

class Account implements AccountInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    protected $lft;

    /**
     * @var int
     */
    protected $rgt;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var UserInterface
     * @Exclude
     */
    private $changer;

    /**
     * @var UserInterface
     * @Exclude
     */
    private $creator;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $corporation;

    /**
     * @var string
     */
    private $uid;

    /**
     * @var string
     */
    private $registerNumber;

    /**
     * @var string
     */
    private $placeOfJurisdiction;

    /**
     * @var string
     */
    private $mainEmail;

    /**
     * @var string
     */
    private $mainPhone;

    /**
     * @var string
     */
    private $mainFax;

    /**
     * @var string
     */
    private $mainUrl;

    /**
     * @var ContactInterface
     */
    private $mainContact;

    /**
     * @var MediaInterface
     */
    protected $logo;

    /**
     * @var Collection
     * @Exclude
     */
    protected $children;

    /**
     * @var AccountInterface
     */
    protected $parent;

    /**
     * @var string
     * @Accessor(getter="getAddresses")
     */
    protected $addresses;

    /**
     * @var Collection
     */
    protected $urls;

    /**
     * @var Collection
     */
    protected $phones;

    /**
     * @var Collection
     */
    protected $socialMediaProfiles;

    /**
     * @var Collection
     */
    protected $emails;

    /**
     * @var Collection
     *
     * @deprecated
     */
    protected $notes;

    /**
     * @var string
     */
    protected $note;

    /**
     * @var Collection
     */
    protected $faxes;

    /**
     * @var Collection
     */
    protected $bankAccounts;

    /**
     * @var Collection
     * @Accessor(getter="getTagNameArray")
     */
    protected $tags;

    /**
     * @var Collection
     */
    protected $accountContacts;

    /**
     * @var Collection
     * @Exclude
     */
    protected $accountAddresses;

    /**
     * @var Collection
     */
    protected $medias;

    /**
     * @var Collection
     */
    protected $categories;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->urls = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->phones = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->faxes = new ArrayCollection();
        $this->socialMediaProfiles = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->accountContacts = new ArrayCollection();
        $this->accountAddresses = new ArrayCollection();
        $this->bankAccounts = new ArrayCollection();
        $this->medias = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function setLft(int $lft): AccountInterface
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setRgt(int $rgt): AccountInterface
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function setDepth(int $depth): AccountInterface
    {
        $this->depth = $depth;

        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setParent(?AccountInterface $parent = null): AccountInterface
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?AccountInterface
    {
        return $this->parent;
    }

    public function addUrl(Url $url): AccountInterface
    {
        $this->urls[] = $url;

        return $this;
    }

    public function removeUrl(Url $url): AccountInterface
    {
        $this->urls->removeElement($url);

        return $this;
    }

    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function addPhone(Phone $phone): AccountInterface
    {
        $this->phones[] = $phone;

        return $this;
    }

    public function removePhone(Phone $phone): AccountInterface
    {
        $this->phones->removeElement($phone);

        return $this;
    }

    public function getPhones(): Collection
    {
        return $this->phones;
    }

    public function addEmail(Email $email): AccountInterface
    {
        $this->emails[] = $email;

        return $this;
    }

    public function removeEmail(Email $email): AccountInterface
    {
        $this->emails->removeElement($email);

        return $this;
    }

    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function addNote(Note $note): AccountInterface
    {
        $this->notes[] = $note;

        return $this;
    }

    public function removeNote(Note $note): AccountInterface
    {
        $this->notes->removeElement($note);

        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addChildren(AccountInterface $children): AccountInterface
    {
        $this->children[] = $children;

        return $this;
    }

    public function removeChildren(AccountInterface $children): AccountInterface
    {
        $this->children->removeElement($children);

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addFax(Fax $fax): AccountInterface
    {
        $this->faxes[] = $fax;

        return $this;
    }

    public function removeFax(Fax $fax): AccountInterface
    {
        $this->faxes->removeElement($fax);

        return $this;
    }

    public function getFaxes(): Collection
    {
        return $this->faxes;
    }

    public function addSocialMediaProfile(SocialMediaProfile $socialMediaProfile): AccountInterface
    {
        $this->socialMediaProfiles[] = $socialMediaProfile;

        return $this;
    }

    public function removeSocialMediaProfile(SocialMediaProfile $socialMediaProfile): AccountInterface
    {
        $this->socialMediaProfiles->removeElement($socialMediaProfile);

        return $this;
    }

    public function getSocialMediaProfiles(): Collection
    {
        return $this->socialMediaProfiles;
    }

    public function addBankAccount(BankAccount $bankAccount): AccountInterface
    {
        $this->bankAccounts[] = $bankAccount;

        return $this;
    }

    public function removeBankAccount(BankAccount $bankAccount): AccountInterface
    {
        $this->bankAccounts->removeElement($bankAccount);

        return $this;
    }

    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    public function addTag(TagInterface $tag): AccountInterface
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function removeTag(TagInterface $tag): AccountInterface
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagNameArray(): array
    {
        $tags = [];
        if (!is_null($this->getTags())) {
            foreach ($this->getTags() as $tag) {
                $tags[] = $tag->getName();
            }
        }

        return $tags;
    }

    public function addAccountContact(AccountContact $accountContact): AccountInterface
    {
        $this->accountContacts[] = $accountContact;

        return $this;
    }

    public function removeAccountContact(AccountContact $accountContact): AccountInterface
    {
        $this->accountContacts->removeElement($accountContact);

        return $this;
    }

    public function getAccountContacts(): Collection
    {
        return $this->accountContacts;
    }

    public function addAccountAddress(AccountAddress $accountAddress): AccountInterface
    {
        $this->accountAddresses[] = $accountAddress;

        return $this;
    }

    public function removeAccountAddress(AccountAddress $accountAddress): AccountInterface
    {
        $this->accountAddresses->removeElement($accountAddress);

        return $this;
    }

    public function getAccountAddresses(): Collection
    {
        return $this->accountAddresses;
    }

    /**
     * @return Address[]
     */
    public function getAddresses(): array
    {
        $accountAddresses = $this->getAccountAddresses();
        $addresses = [];

        if (!is_null($accountAddresses)) {
            /* @var ContactAddress $contactAddress */
            foreach ($accountAddresses as $accountAddress) {
                $address = $accountAddress->getAddress();
                $address->setPrimaryAddress($accountAddress->getMain());
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    public function getMainAddress(): ?Address
    {
        $accountAddresses = $this->getAccountAddresses();

        if (!is_null($accountAddresses)) {
            /** @var AccountAddress $accountAddress */
            foreach ($accountAddresses as $accountAddress) {
                if ($accountAddress->getMain()) {
                    return $accountAddress->getAddress();
                }
            }
        }

        return null;
    }

    /**
     * @return ContactInterface[]
     */
    public function getContacts(): array
    {
        $accountContacts = $this->getAccountContacts();
        $contacts = [];

        if (!is_null($accountContacts)) {
            /** @var AccountContact $accountContact */
            foreach ($accountContacts as $accountContact) {
                $contacts[] = $accountContact->getContact();
            }
        }

        return $contacts;
    }

    public function addMedia(MediaInterface $media): AccountInterface
    {
        $this->medias[] = $media;

        return $this;
    }

    public function removeMedia(MediaInterface $media): AccountInterface
    {
        $this->medias->removeElement($media);

        return $this;
    }

    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addChild(AccountInterface $children): AccountInterface
    {
        $this->children[] = $children;

        return $this;
    }

    public function removeChild(AccountInterface $children): AccountInterface
    {
        $this->children->removeElement($children);

        return $this;
    }

    public function addCategory(CategoryInterface $category): AccountInterface
    {
        $this->categories[] = $category;

        return $this;
    }

    public function removeCategory(CategoryInterface $category): AccountInterface
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setId($id): AccountInterface
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): AccountInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setExternalId(string $externalId): AccountInterface
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setNumber(string $number): AccountInterface
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setCorporation(?string $corporation): AccountInterface
    {
        $this->corporation = $corporation;

        return $this;
    }

    public function getCorporation(): ?string
    {
        return $this->corporation;
    }

    public function setUid(string $uid): AccountInterface
    {
        $this->uid = $uid;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setRegisterNumber(string $registerNumber): AccountInterface
    {
        $this->registerNumber = $registerNumber;

        return $this;
    }

    public function getRegisterNumber(): ?string
    {
        return $this->registerNumber;
    }

    public function setPlaceOfJurisdiction(string $placeOfJurisdiction): AccountInterface
    {
        $this->placeOfJurisdiction = $placeOfJurisdiction;

        return $this;
    }

    public function getPlaceOfJurisdiction(): ?string
    {
        return $this->placeOfJurisdiction;
    }

    public function setMainEmail(?string $mainEmail = null): AccountInterface
    {
        $this->mainEmail = $mainEmail;

        return $this;
    }

    public function getMainEmail(): ?string
    {
        return $this->mainEmail;
    }

    public function setMainPhone(?string $mainPhone = null): AccountInterface
    {
        $this->mainPhone = $mainPhone;

        return $this;
    }

    public function getMainPhone(): ?string
    {
        return $this->mainPhone;
    }

    public function setMainFax(?string $mainFax = null): AccountInterface
    {
        $this->mainFax = $mainFax;

        return $this;
    }

    public function setLogo(MediaInterface $logo): AccountInterface
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogo(): ?MediaInterface
    {
        return $this->logo;
    }

    public function getMainFax(): ?string
    {
        return $this->mainFax;
    }

    public function setMainUrl(?string $mainUrl = null): AccountInterface
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }

    public function getMainUrl(): ?string
    {
        return $this->mainUrl;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): AccountInterface
    {
        $this->created = $created;

        return $this;
    }

    public function getChanged(): \DateTime
    {
        return $this->changed;
    }

    public function setChanged(\DateTime $changed): AccountInterface
    {
        $this->changed = $changed;

        return $this;
    }

    public function getChanger(): ?UserInterface
    {
        return $this->changer;
    }

    public function setChanger(UserInterface $changer): AccountInterface
    {
        $this->changer = $changer;

        return $this;
    }

    public function getCreator(): ?UserInterface
    {
        return $this->creator;
    }

    public function setCreator($creator): AccountInterface
    {
        $this->creator = $creator;

        return $this;
    }

    public function getMainContact(): ?ContactInterface
    {
        return $this->mainContact;
    }

    public function setMainContact(?ContactInterface $mainContact = null): AccountInterface
    {
        $this->mainContact = $mainContact;

        return $this;
    }
}
