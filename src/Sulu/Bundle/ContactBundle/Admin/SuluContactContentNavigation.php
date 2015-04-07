<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluContactContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $this->setName('Contacts');

        /* CONTACTS */

        // details
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setGroups(array('contact'));
        $details->setComponent('contacts@sulucontact');
        $details->setComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);

        // documents
        $documents = new ContentNavigationItem('content-navigation.contacts.documents');
        $documents->setAction('documents');
        $documents->setGroups(array('contact'));
        $documents->setComponent('contacts@sulucontact');
        $documents->setComponentOptions(array('display'=>'documents'));
        $documents->setDisplay(array('edit'));
        $this->addNavigationItem($documents);

        /* ACCOUNTS */

        // details
        $details = new ContentNavigationItem('content-navigation.contacts.details');
        $details->setAction('details');
        $details->setId('details');
        $details->setGroups(array('account'));
        $details->setComponent('accounts@sulucontact');
        $details->setComponentOptions(array('display'=>'form'));
        $this->addNavigationItem($details);

        // contacts
        $contacts = new ContentNavigationItem('content-navigation.contact.accounts.contacts');
        $contacts->setAction('contacts');
        $contacts->setId('contacts');
        $contacts->setGroups(array('account'));
        $contacts->setComponent('accounts@sulucontact');
        $contacts->setComponentOptions(array('display'=>'contacts'));
        $contacts->setDisplay(array('edit'));
        $this->addNavigationItem($contacts);

        // documents
        $documents = new ContentNavigationItem('content-navigation.accounts.documents');
        $documents->setAction('documents');
        $documents->setGroups(array('account'));
        $documents->setComponent('accounts@sulucontact');
        $documents->setComponentOptions(array('display'=>'documents'));
        $documents->setDisplay(array('edit'));
        $this->addNavigationItem($documents);
    }
}
