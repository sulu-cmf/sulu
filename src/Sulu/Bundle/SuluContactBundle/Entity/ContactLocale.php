<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactLocale
 */
class ContactLocale
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    private $contact;


    /**
     * Set locale
     *
     * @param string $locale
     * @return ContactLocale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contact
     *
     * @param \Sulu\Bundle\ContactBundle\Entity\Contact $contact
     * @return ContactLocale
     */
    public function setContact(\Sulu\Bundle\ContactBundle\Entity\Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Sulu\Bundle\ContactBundle\Entity\Contact
     */
    public function getContact()
    {
        return $this->contact;
    }
}