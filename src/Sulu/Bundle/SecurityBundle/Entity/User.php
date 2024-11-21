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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorTrait;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * User.
 *
 * @ExclusionPolicy("all")
 */
class User extends ApiEntity implements UserInterface, EquatableInterface, AuditableInterface, PasswordAuthenticatedUserInterface
{
    use AuditableTrait;
    use TwoFactorTrait;

    /**
     * @var int
     *
     * @Expose
     * @Groups({"frontend", "fullUser"})
     */
    protected $id;

    /**
     * @var string
     *
     * @Expose
     * @Groups({"frontend", "fullUser", "profile"})
     */
    protected $username;

    /**
     * @var string|null
     *
     * @Expose
     * @Groups({"fullUser", "profile"})
     */
    protected $email;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     *
     * @Expose
     * @Groups({"frontend", "fullUser", "profile"})
     */
    protected $locale;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @var string|null
     *
     * @Expose
     */
    protected $privateKey;

    /**
     * @var string|null
     */
    protected $apiKey;

    /**
     * @var bool
     *
     * @Expose
     */
    protected $locked = false;

    /**
     * @var bool
     *
     * @Expose
     */
    protected $enabled = true;

    /**
     * @var \DateTime|null
     */
    protected $lastLogin;

    /**
     * @var string|null
     */
    protected $confirmationKey;

    /**
     * @var string|null
     */
    protected $passwordResetToken;

    /**
     * @var \DateTime|null
     */
    private $passwordResetTokenExpiresAt;

    /**
     * @var int|null
     */
    private $passwordResetTokenEmailsSent;

    /**
     * @var ContactInterface
     *
     * @Expose
     * @Groups({"frontend", "fullUser"})
     */
    protected $contact;

    /**
     * @var Collection|UserRole[]
     *
     * @Expose
     */
    protected $userRoles;

    /**
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @var Collection|UserGroup[]
     *
     * @Expose
     */
    protected $userGroups;

    /**
     * @var Collection|UserSetting[]
     */
    protected $userSettings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apiKey = \md5(\uniqid());

        $this->userRoles = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
        $this->userSettings = new ArrayCollection();
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
     * Set username.
     *
     * @param string $username
     *
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username.
     *
     * @SerializedName("username")
     * @Groups({"frontend", "fullUser"})
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * Set password.
     *
     * @param string $password
     *
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return self
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set salt.
     *
     * @param string $salt
     *
     * @return self
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt.
     *
     * @deprecated The salt functionality was deprecated in Sulu 2.5 and will be removed in Sulu 3.0
     *             Modern password algorithm do not longer require a salt.
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set privateKey.
     *
     * @param string|null $privateKey
     *
     * @return self
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get privateKey.
     *
     * @return string|null
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Removes the password of the user.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Set apiKey.
     *
     * @param string|null $apiKey
     *
     * @return self
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set locked.
     *
     * @param bool $locked
     *
     * @return self
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set enabled.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set lastLogin.
     *
     * @param \DateTime|null $lastLogin
     *
     * @return self
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin.
     *
     * @return \DateTime|null
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set confirmationKey.
     *
     * @param string|null $confirmationKey
     *
     * @return self
     */
    public function setConfirmationKey($confirmationKey)
    {
        $this->confirmationKey = $confirmationKey;

        return $this;
    }

    /**
     * Get confirmationKey.
     *
     * @return string|null
     */
    public function getConfirmationKey()
    {
        return $this->confirmationKey;
    }

    /**
     * Set passwordResetToken.
     *
     * @param string|null $passwordResetToken
     *
     * @return self
     */
    public function setPasswordResetToken($passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * Get passwordResetToken.
     *
     * @return string|null
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set tokenExpiresAt.
     *
     * @param \DateTime|null $passwordResetTokenExpiresAt
     *
     * @return self
     */
    public function setPasswordResetTokenExpiresAt($passwordResetTokenExpiresAt)
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    /**
     * Get passwordResetTokenExpiresAt.
     *
     * @return \DateTime|null
     */
    public function getPasswordResetTokenExpiresAt()
    {
        return $this->passwordResetTokenExpiresAt;
    }

    /**
     * Set passwordResetTokenEmailsSent.
     *
     * @param int|null $passwordResetTokenEmailsSent
     *
     * @return self
     */
    public function setPasswordResetTokenEmailsSent($passwordResetTokenEmailsSent)
    {
        $this->passwordResetTokenEmailsSent = $passwordResetTokenEmailsSent;

        return $this;
    }

    /**
     * Get passwordResetTokenEmailsSent.
     *
     * @return int|null
     */
    public function getPasswordResetTokenEmailsSent()
    {
        return $this->passwordResetTokenEmailsSent;
    }

    public function isEqualTo(SymfonyUserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->id === $user->getId()
            && $this->password === $user->getPassword()
            && $this->salt === $user->getSalt()
            && $this->username === $user->getUsername()
            && $this->locked === $user->getLocked()
            && $this->enabled === $user->getEnabled();
    }

    /**
     * Add userRoles.
     *
     * @return self
     */
    public function addUserRole(UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles.
     */
    public function removeUserRole(UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles.
     *
     * @return ArrayCollection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * @VirtualProperty
     * @Groups({"frontend"})
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        foreach ($this->getUserRoles() as $userRole) {
            /* @var UserRole $userRole */
            $roles[] = $userRole->getRole()->getIdentifier();
        }

        return $roles;
    }

    public function getRoleObjects()
    {
        $roles = [];
        foreach ($this->getUserRoles() as $userRole) {
            $roles[] = $userRole->getRole();
        }

        return $roles;
    }

    /**
     * Add userGroups.
     *
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @return self
     */
    public function addUserGroup(UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups.
     *
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     */
    public function removeUserGroup(UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups.
     *
     * @deprecated The group functionality was deprecated in Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @return ArrayCollection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Add userSettings.
     *
     * @return self
     */
    public function addUserSetting(UserSetting $userSettings)
    {
        $this->userSettings[] = $userSettings;

        return $this;
    }

    /**
     * Remove userSettings.
     */
    public function removeUserSetting(UserSetting $userSettings)
    {
        $this->userSettings->removeElement($userSettings);
    }

    /**
     * Get userSettings.
     *
     * @return Collection|UserSetting[]
     */
    public function getUserSettings()
    {
        return $this->userSettings;
    }

    /**
     * @VirtualProperty
     * @Groups({"frontend"})
     */
    public function getSettings()
    {
        $userSettingValues = [];
        foreach ($this->userSettings as $userSetting) {
            $userSettingValues[$userSetting->getKey()] = \json_decode($userSetting->getValue(), true);
        }

        return $userSettingValues;
    }

    /**
     * Set contact.
     *
     * @return self
     */
    public function setContact(?ContactInterface $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return ContactInterface
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @VirtualProperty
     * @SerializedName("fullName")
     * @Groups({"frontend", "fullUser"})
     *
     * @return string
     */
    public function getFullName()
    {
        return null !== $this->getContact() ?
            $this->getContact()->getFullName() : $this->getUsername();
    }

    /**
     * @VirtualProperty
     * @Groups({"profile"})
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->contact->getFirstName();
    }

    /**
     * Set firstName.
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->contact->setFirstName($firstName);

        return $this;
    }

    /**
     * @VirtualProperty
     * @Groups({"profile"})
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->contact->getLastName();
    }

    /**
     * Set lastName.
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->contact->setLastName($lastName);

        return $this;
    }
}
