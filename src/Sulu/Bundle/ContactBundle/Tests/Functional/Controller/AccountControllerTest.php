<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountAddress;
use Sulu\Bundle\ContactBundle\Entity\AccountContact;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\Fax;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\Note;
use Sulu\Bundle\ContactBundle\Entity\Phone;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\Url;
use Sulu\Bundle\ContactBundle\Entity\UrlType;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AccountControllerTest extends SuluTestCase
{
    private $accountCount = 1;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var ObjectRepository<ActivityInterface>
     */
    private $activityRepository;

    /**
     * @var ObjectRepository<TrashItemInterface>
     */
    private $trashItemRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->activityRepository = $this->em->getRepository(ActivityInterface::class);
        $this->trashItemRepository = $this->em->getRepository(TrashItemInterface::class);
    }

    /**
     * Tests if all accounts are returned when fetching flat api by ids.
     */
    public function testCGetByIdsOnFlatApi(): void
    {
        $amount = 11;

        $accounts = $this->createMultipleMinimalAccounts($amount);
        $this->em->flush();
        $this->em->clear();

        // Get ids of new accounts.
        $ids = \array_map(
            function($account) {
                return $account->getId();
            },
            $accounts
        );

        // Make get request on flat api.

        $this->client->jsonRequest(
            'GET',
            '/api/accounts?flat=true',
            [
                'ids' => \implode(',', $ids),
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount($amount, $response->_embedded->accounts);
    }

    /**
     * Tests if all accounts are returned when fetching flat api by ids.
     */
    public function testCGetByIdsOnFlatApiWithLimit(): void
    {
        $accounts = $this->createMultipleMinimalAccounts(11);
        $this->em->flush();
        $this->em->clear();

        // Get ids of new accounts.
        $ids = \array_map(
            function($account) {
                return $account->getId();
            },
            $accounts
        );

        // Make get request on flat api.

        $this->client->jsonRequest(
            'GET',
            '/api/accounts?flat=true',
            [
                'ids' => \implode(',', $ids),
                'page' => 2,
                'limit' => 10,
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $response->_embedded->accounts);
    }

    public function testCgetSerializationExclusions(): void
    {
        $account = $this->createAccount('Company');
        $contact = $this->createContact($account, 'Vorname', 'Nachname');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('account', $response['_embedded']['accounts'][0]['accountContacts'][0]['contact']);
        $this->assertArrayNotHasKey('account', $response['_embedded']['accounts'][0]['contacts'][0]);
    }

    public function testGetById(): void
    {
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('office@company.example', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $note = $this->createNote('Note');
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $account = $this->createAccount(
            'Company',
            null,
            $url,
            $address,
            $email,
            $phone,
            $fax,
            $note,
            'Feldkirch',
            $logo
        );
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $account->getId()
        );

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('Company', $response->name);
        $this->assertEquals('http://www.company.example', $response->contactDetails->websites[0]->website);
        $this->assertEquals($urlType->getId(), $response->contactDetails->websites[0]->websiteType);
        $this->assertEquals('office@company.example', $response->contactDetails->emails[0]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[0]->emailType);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[0]->phoneType);
        $this->assertEquals('123654789', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals($faxType->getId(), $response->contactDetails->faxes[0]->faxType);
        $this->assertEquals('Note', $response->notes[0]->value);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterland', $response->addresses[0]->state);
        $this->assertEquals('ML', $response->addresses[0]->countryCode);
        $this->assertEquals($addressType->getId(), $response->addresses[0]->addressType);
        $this->assertEquals(47.4048346, $response->addresses[0]->latitude);
        $this->assertEquals(9.7602198, $response->addresses[0]->longitude);
        $this->assertEquals('Feldkirch', $response->placeOfJurisdiction);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $this->assertTrue(\property_exists($response, 'logo'));
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertTrue(\property_exists($response->logo, 'thumbnails'));
        $this->assertTrue(\property_exists($response->logo->thumbnails, 'sulu-100x100'));
        $this->assertTrue(\is_string($response->logo->thumbnails->{'sulu-100x100'}));
    }

    public function testGetByIdNotExisting(): void
    {
        $this->client->jsonRequest(
            'GET',
            '/api/accounts/11230'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertObjectHasProperty('message', $response);
    }

    public function testGetEmptyAccountContacts(): void
    {
        $account = new Account();
        $account->setName('test');

        $this->em->persist($account);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId() . '/contacts?flat=true');

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(0, $response->total);
        $this->assertCount(0, $response->_embedded->account_contacts);
    }

    public function testGetAccountContacts(): void
    {
        $account = new Account();
        $account->setName('test');
        $this->em->persist($account);

        $account2 = new Account();
        $account2->setName('test2');
        $this->em->persist($account2);

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $accountContact2 = new AccountContact();
        $accountContact2->setContact($contact);
        $accountContact2->setAccount($account2);
        $accountContact2->setMain(false);
        $account2->addAccountContact($accountContact2);
        $this->em->persist($accountContact2);

        $contact = new Contact();
        $contact->setFirstName('Erika');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(1);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $account->addAccountContact($accountContact);
        $accountContact->setMain(false);
        $this->em->persist($accountContact);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $account->getId() . '/contacts?flat=true&fields=firstName&sortBy=firstName'
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['account_contacts']);

        $this->assertEquals('Erika', $response['_embedded']['account_contacts'][0]['firstName']);
        $this->assertEquals('Max', $response['_embedded']['account_contacts'][1]['firstName']);
    }

    public function testGetAccountContactsSearch(): void
    {
        $account = new Account();
        $account->setName('test');
        $this->em->persist($account);

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $contact = new Contact();
        $contact->setFirstName('Erika');
        $contact->setLastName('Mustermann');
        $contact->setFormOfAddress(1);
        $this->em->persist($contact);

        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $account->addAccountContact($accountContact);
        $accountContact->setMain(false);
        $this->em->persist($accountContact);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $account->getId() . '/contacts?search=Max&searchFields=fullName&flat=true&fields=fullName'
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(1, $response['total']);
        $this->assertCount(1, $response['_embedded']['account_contacts']);

        $this->assertEquals('Max Mustermann', $response['_embedded']['account_contacts'][0]['fullName']);
    }

    public function testPost(): void
    {
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);
        $account = $this->createAccount('parent');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $urlType = $this->createUrlType('Private');
        $faxType = $this->createFaxType('Private');
        $addressType = $this->createAddressType('Private');
        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'note' => 'A small notice',
                'parent' => ['id' => $account->getId()],
                'logo' => ['id' => $logo->getId()],
                'contactDetails' => [
                    'websites' => [
                        [
                            'website' => 'http://example.company.com',
                            'websiteType' => $urlType->getId(),
                        ],
                    ],
                    'emails' => [
                        [
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                        [
                            'email' => 'erika.mustermann@muster.de',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '987654321',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => '123456789-1',
                            'faxType' => $faxType->getId(),
                        ],
                        [
                            'fax' => '987654321-1',
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                        'latitude' => 47.4049309,
                        'longitude' => 9.7593077,
                    ],
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                    ['value' => 'Note 2'],
                ],
                'categories' => [
                    $category1->getId(),
                    $category2->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'created']);
        $this->assertSame((string) $response->id, $activity->getResourceId());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals('A small notice', $response->note);
        $this->assertEquals(1, $response->depth);
        $this->assertEquals($account->getId(), $response->parent->id);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('erika.mustermann@muster.de', $response->contactDetails->emails[1]->email);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('987654321', $response->contactDetails->phones[1]->phone);
        $this->assertEquals('123456789-1', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals('987654321-1', $response->contactDetails->faxes[1]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('note', $response->addresses[0]->note);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);
        $this->assertEquals('Note 2', $response->notes[1]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
        $this->assertEquals(47.4049309, $response->addresses[0]->latitude);
        $this->assertEquals(9.7593077, $response->addresses[0]->longitude);

        $this->assertTrue(\property_exists($response, 'logo'));
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertTrue(\property_exists($response->logo, 'thumbnails'));
        $this->assertTrue(\property_exists($response->logo->thumbnails, 'sulu-100x100'));
        $this->assertTrue(\is_string($response->logo->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(2, \count($response->categories));
        $this->assertEquals($category1->getId(), $response->categories[0]);
        $this->assertEquals($category2->getId(), $response->categories[1]);
    }

    public function testPostWithNullContactDetails(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('ExampleCompany', $response->name);
    }

    public function testPostWithNullLogo(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'logo' => null,
                'corporation' => null,
                'note' => null,
                'parent' => null,
                'uid' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('ExampleCompany', $response->name);
    }

    public function testPostWithIds(): void
    {
        $phoneType = $this->createPhoneType('Private');
        $urlType = $this->createUrlType('Private');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'websites' => [
                        [
                            'id' => 1512312312313,
                            'website' => 'http://example.company.com',
                            'websiteType' => $urlType->getId(),
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertStringContainsString('15', $response->message);

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'emails' => [
                        [
                            'id' => 16,
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => 1,
                        ],
                        [
                            'email' => 'erika.mustermann@muster.de',
                            'emailType' => 1,
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertStringContainsString('16', $response->message);

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'phones' => [
                        [
                            'id' => 17,
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '987654321',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertStringContainsString('17', $response->message);

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'id' => 18,
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => 1,
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertStringContainsString('18', $response->message);

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'notes' => [
                    [
                        'id' => 19,
                        'value' => 'Note',
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertStringContainsString('19', $response->message);
    }

    public function testPostWithNotExistingUrlType(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'websites' => [
                        [
                            'website' => 'http://example.company.com',
                            'websiteType' => '12312',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPostWithNotExistingEmailType(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'emails' => [
                        [
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => 1,
                        ],
                        [
                            'email' => 'erika.mustermann@muster.de',
                            'emailType' => 2,
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPostWithNotExistingPhoneType(): void
    {
        $phoneType = $this->createPhoneType('Private');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'phones' => [
                        [
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '987654321',
                            'phoneType' => '1233',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPostWithNotExistingAddressType(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => 2,
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPostWithNotExistingFaxType(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'faxes' => [
                        [
                            'fax' => '12345',
                            'faxType' => '123123',
                        ],
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testPostWithNotExistingCountry(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ooo',
                        'addressType' => 1,
                    ],
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertObjectHasProperty('message', $response);
    }

    public function testGetList(): void
    {
        $this->createAccount('Company');
        $parent = $this->createAccount('Parent');
        $this->createAccount('Child', $parent);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/api/accounts?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);

        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testGetListSearch(): void
    {
        $this->createAccount('Company');
        $this->createAccount('Something');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/api/accounts?flat=true&search=Nothing&searchFields=name');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, \count($response->_embedded->accounts));

        $this->client->jsonRequest('GET', '/api/accounts?flat=true&search=Comp&searchFields=name');
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, \count($response->_embedded->accounts));
        $this->assertEquals('Company', $response->_embedded->accounts[0]->name);
    }

    public function testPut(): void
    {
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $faxType = $this->createFaxType('Private');
        $addressType = $this->createAddressType('Private');
        $category1 = $this->createCategory('first-category-key', 'en', 'First Category', 'Description of Category');
        $category2 = $this->createCategory('second-category-key', 'en', 'Second Category', 'Description of second Category');
        $category3 = $this->createCategory('third-category-key', 'en', 'Third Category', 'Description of third Category');
        $account = $this->createAccount(
            'Company',
            null,
            $url,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            [
                $category1,
                $category2,
            ]
        );
        $logo = $this->createMedia('logo.jpeg', 'image/jpeg', $mediaType, $collection);
        $contact = $this->createContact($account, 'Vorname', 'Nachname');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'note' => 'A small notice',
                'logo' => ['id' => $logo->getId()],
                'mainContact' => ['id' => $contact->getId()],
                'contactDetails' => [
                    'websites' => [
                        [
                            'id' => $url->getId(),
                            'website' => 'http://example.company.com',
                            'websiteType' => $urlType->getId(),
                        ],
                        [
                            'website' => 'http://test.company.com',
                            'websiteType' => $urlType->getId(),
                        ],
                    ],
                    'emails' => [
                        [
                            'email' => 'office@company.com',
                            'emailType' => $emailType->getId(),
                        ],
                        [
                            'email' => 'erika.mustermann@company.com',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '4567890',
                            'phoneType' => $phoneType->getId(),
                        ],
                        [
                            'phone' => '789456123',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => '4567890-1',
                            'faxType' => $faxType->getId(),
                        ],
                        [
                            'fax' => '789456123-1',
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                        'note' => 'note',
                        'latitude' => 47.4048168,
                        'longitude' => 9.7585263,
                    ],
                    [
                        'street' => 'Rathausgasse',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'note' => 'note1',
                    ],
                ],
                'notes' => [
                    ['value' => 'Note1'],
                    ['value' => 'Note2'],
                ],
                'categories' => [
                    $category3->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'modified']);
        $this->assertSame((string) $response->id, $activity->getResourceId());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals('A small notice', $response->note);
        $this->assertEquals($contact->getId(), $response->mainContact->id);

        $this->assertEquals(2, \count($response->contactDetails->websites));
        $this->assertEquals('http://example.company.com', $response->contactDetails->websites[0]->website);
        $this->assertEquals($urlType->getId(), $response->contactDetails->websites[0]->websiteType);
        $this->assertEquals('http://test.company.com', $response->contactDetails->websites[1]->website);
        $this->assertEquals($urlType->getId(), $response->contactDetails->websites[1]->websiteType);

        $this->assertEquals(2, \count($response->contactDetails->emails));
        $this->assertEquals('office@company.com', $response->contactDetails->emails[0]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[0]->emailType);
        $this->assertEquals('erika.mustermann@company.com', $response->contactDetails->emails[1]->email);
        $this->assertEquals($emailType->getId(), $response->contactDetails->emails[1]->emailType);

        $this->assertEquals(2, \count($response->contactDetails->phones));
        $this->assertEquals('4567890', $response->contactDetails->phones[0]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[0]->phoneType);
        $this->assertEquals('789456123', $response->contactDetails->phones[1]->phone);
        $this->assertEquals($phoneType->getId(), $response->contactDetails->phones[1]->phoneType);

        $this->assertEquals(2, \count($response->contactDetails->faxes));
        $this->assertEquals('4567890-1', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals($faxType->getId(), $response->contactDetails->faxes[0]->faxType);
        $this->assertEquals('789456123-1', $response->contactDetails->faxes[1]->fax);
        $this->assertEquals($faxType->getId(), $response->contactDetails->faxes[1]->faxType);

        $this->assertEquals(2, \count($response->notes));
        $this->assertEquals('Note1', $response->notes[0]->value);
        $this->assertEquals('Note2', $response->notes[1]->value);

        $this->assertTrue(\property_exists($response, 'logo'));
        $this->assertEquals($logo->getId(), $response->logo->id);
        $this->assertTrue(\property_exists($response->logo, 'thumbnails'));
        $this->assertTrue(\property_exists($response->logo->thumbnails, 'sulu-100x100'));
        $this->assertTrue(\is_string($response->logo->thumbnails->{'sulu-100x100'}));

        $this->assertEquals(1, \count($response->categories));
        $this->assertEquals($category3->getId(), $response->categories[0]);

        if ('Bahnhofstraße' === $response->addresses[0]->street) {
            $this->assertEquals(2, \count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[0]->street);
            $this->assertEquals('2', $response->addresses[0]->number);
            $this->assertEquals('0022', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('note', $response->addresses[0]->note);
            $this->assertEquals('ML', $response->addresses[0]->countryCode);
            $this->assertEquals($addressType->getId(), $response->addresses[0]->addressType);

            $this->assertEquals(true, $response->addresses[0]->billingAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
            $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
            $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
            $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

            $this->assertEquals(47.4048168, $response->addresses[0]->latitude);
            $this->assertEquals(9.7585263, $response->addresses[0]->longitude);

            $this->assertEquals('Rathausgasse', $response->addresses[1]->street);
            $this->assertEquals('3', $response->addresses[1]->number);
            $this->assertEquals('2222', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('note1', $response->addresses[1]->note);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('ML', $response->addresses[1]->countryCode);
            $this->assertEquals($addressType->getId(), $response->addresses[1]->addressType);
        } else {
            $this->assertEquals(2, \count($response->addresses));
            $this->assertEquals('Bahnhofstraße', $response->addresses[1]->street);
            $this->assertEquals('2', $response->addresses[1]->number);
            $this->assertEquals('note', $response->addresses[1]->note);
            $this->assertEquals('0022', $response->addresses[1]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[1]->city);
            $this->assertEquals('state1', $response->addresses[1]->state);
            $this->assertEquals('ML', $response->addresses[1]->countryCode);
            $this->assertEquals($addressType->getId(), $response->addresses[1]->addressType);

            $this->assertEquals(true, $response->addresses[1]->billingAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
            $this->assertEquals(false, $response->addresses[1]->deliveryAddress);
            $this->assertEquals('Dornbirn', $response->addresses[1]->postboxCity);
            $this->assertEquals('6850', $response->addresses[1]->postboxPostcode);
            $this->assertEquals('4711', $response->addresses[1]->postboxNumber);

            $this->assertEquals('Rathausgasse', $response->addresses[0]->street);
            $this->assertEquals('3', $response->addresses[0]->number);
            $this->assertEquals('2222', $response->addresses[0]->zip);
            $this->assertEquals('Dornbirn', $response->addresses[0]->city);
            $this->assertEquals('state1', $response->addresses[0]->state);
            $this->assertEquals('ML', $response->addresses[0]->countryCode);
            $this->assertEquals('note1', $response->addresses[0]->note);
            $this->assertEquals($addressType->getId(), $response->addresses[0]->addressType);
        }
    }

    public function testPutNoDetails(): void
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('info@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Company', null, $url, $address, $email, $phone, $fax, $note);
        $account->setUid('Test Uuid');
        $account->setNote('Test Note');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'contactDetails' => [
                    'websites' => [],
                    'emails' => [],
                    'phones' => [],
                    'addresses' => [],
                    'faxes' => [],
                ],
                'notes' => [],
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals(null, $response->uid);
        $this->assertEquals(null, $response->note);
        $this->assertEquals(0, \count($response->contactDetails->websites));
        $this->assertEquals(0, \count($response->contactDetails->emails));
        $this->assertEquals(0, \count($response->contactDetails->phones));
        $this->assertEquals(0, \count($response->contactDetails->faxes));
        $this->assertEquals(0, \count($response->notes));
        $this->assertEquals(0, \count($response->addresses));
    }

    public function testPutWithNullLogo(): void
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $email = $this->createEmail('info@muster.at', $emailType);
        $phoneType = $this->createPhoneType('Private');
        $phone = $this->createPhone('123456789', $phoneType);
        $faxType = $this->createFaxType('Private');
        $fax = $this->createFax('123456789', $faxType);
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress(
            $addressType,
            'Musterstraße',
            '1',
            '0000',
            'Musterstadt',
            'Musterland',
            'ML',
            true,
            true,
            false,
            'Dornbirn',
            '6850',
            '4711',
            'note',
            47.4048346,
            9.7602198
        );
        $note = $this->createNote('Note');
        $account = $this->createAccount('Company', null, $url, $address, $email, $phone, $fax, $note);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'logo' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('ExampleCompany', $response->name);
    }

    public function testPutContacts(): void
    {
        $account = $this->createAccount('Company');
        $contact = $this->createContact(null, 'Max', 'Mustermann');
        $position = $this->createPosition('CEO');

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId() . '/contacts/' . $contact->getId(),
            [
                'position' => $position->getId(),
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'contact_added']);
        $this->assertSame((string) $account->getId(), $activity->getResourceId());
        $this->assertSame('accounts', $activity->getResourceKey());

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId() . '/contacts?flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $accountContacts = $response->_embedded->account_contacts;
        $this->assertCount(1, $accountContacts);
        $this->assertEquals('Max', $accountContacts[0]->firstName);
        $this->assertEquals('Mustermann', $accountContacts[0]->lastName);
        $this->assertEquals('CEO', $accountContacts[0]->position);
    }

    public function testPutWithNullMainContact(): void
    {
        $contact = $this->createContact(null, 'Max', 'Mustermann');
        $account = $this->createAccount(
            'Company',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $contact
        );

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'mainContact' => null,
            ]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(null, $response->mainContact);
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/4711',
            [
                'name' => 'TestCompany',
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPatchNotExisting(): void
    {
        $this->client->jsonRequest(
            'PATCH',
            '/api/accounts/101',
            [
                'medias' => [],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPatchAssignedMedias(): void
    {
        $account = $this->createAccount('Company');
        $mediaType = $this->createMediaType('image');
        $collectionType = $this->createCollectionType('My collection type');
        $collection = $this->createCollection($collectionType);

        $this->em->flush();

        $media1 = $this->createMedia('media1.jpeg', 'image/jpeg', $mediaType, $collection);
        $media2 = $this->createMedia('media2.jpeg', 'image/jpeg', $mediaType, $collection);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));

        // add two medias
        $this->client->jsonRequest(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [
                    $media1->getId(),
                    $media2->getId(),
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->medias);

        // remove medias
        $this->client->jsonRequest(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));

        // missing media
        $this->client->jsonRequest(
            'PATCH',
            '/api/accounts/' . $account->getId(),
            [
                'medias' => [
                    'id' => $media1->getId(),
                    'id' => 101,
                ],
            ]
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, \count($response->medias));
    }

    public function testDeleteById(): void
    {
        $account = $this->createAccount('Company');
        $this->em->flush();
        $this->em->clear();

        $accountId = $account->getId();
        $this->client->jsonRequest('DELETE', '/api/accounts/' . $accountId);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $account->getId(), $activity->getResourceId());

        $trashItem = $this->trashItemRepository->findOneBy(['resourceKey' => 'accounts', 'resourceId' => $accountId]);
        $this->assertNotNull($trashItem);
    }

    public function testDeleteParentById(): void
    {
        $parentAccount = $this->createAccount('Parent Company');
        $childAccount = $this->createAccount('Company', $parentAccount);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('DELETE', '/api/accounts/' . $parentAccount->getId());
        $this->assertHttpStatusCode(409, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($parentAccount->getId(), $response->id);
        $this->assertEquals('Company', $response->items[0]->name);
    }

    public function testAccountAddresses(): void
    {
        $addressType = $this->createAddressType('Private');

        $address = $this->createAddress($addressType, 'Musterstraße', '1', '0000', 'Musterstadt', 'Musterland', 'ML', true, true, false, 'Dornbirn', '6850', '4711', 47.4048346, 9.7602198);
        $account = $this->createAccount('Company', null, null, $address);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId() . '/addresses');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße', $address->street);
        $this->assertEquals('1', $address->number);

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId() . '/addresses?flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);

        $address = $response->_embedded->addresses[0];
        $this->assertEquals('Musterstraße 1 , 0000, Musterstadt, Musterland, ML, 4711', $address->address);
        $this->assertNotNull($address->id);
    }

    public function testDeleteByIdAndNotDeleteContacts(): void
    {
        $account = $this->createAccount('Company');
        $contact = $this->createContact($account, 'Vorname', 'Nachname');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'DELETE',
            '/api/accounts/' . $account->getId(),
            [
                'removeContacts' => 'false',
            ]
        );
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        // check if contacts are still there
        $this->client->jsonRequest('GET', '/api/contacts?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(2, $response->total);
    }

    public function testDeleteByIdAndDeleteContacts(): void
    {
        $account = $this->createAccount('Company');

        $contact = new Contact();
        $contact->setFirstName('Vorname');
        $contact->setLastName('Nachname');
        $contact->setMiddleName('Mittelname');
        $contact->setFormOfAddress(0);
        $this->em->persist($contact);
        $accountContact = new AccountContact();
        $accountContact->setContact($contact);
        $accountContact->setAccount($account);
        $accountContact->setMain(true);
        $account->addAccountContact($accountContact);
        $this->em->persist($accountContact);

        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'DELETE',
            '/api/accounts/' . $account->getId(),
            [
                'removeContacts' => 'true',
            ]
        );
        // check if contacts are still there
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->findOneBy(['type' => 'removed']);
        $this->assertSame((string) $account->getId(), $activity->getResourceId());

        $this->client->jsonRequest('GET', '/api/contacts?flat=true');
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testDeleteByIdNotExisting(): void
    {
        $this->client->jsonRequest('DELETE', '/api/accounts/4711');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * Test if deleteinfo returns correct data.
     */
    public function testMultipleDeleteInfo(): void
    {
        $account = $this->createAccount('Company');

        // modify test data
        $acc = new Account();
        $acc->setName('Test Account');
        $this->em->persist($acc);

        // add 5 contacts to account
        for ($i = 0; $i < 5; ++$i) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $account->addAccountContact($accountContact);
        }

        // add subaccount to $this->account
        $subacc = new Account();
        $subacc->setName('Subaccount');
        $subacc->setParent($account);

        $this->em->persist($subacc);

        $this->em->flush();
        $this->em->clear();

        // get number of contacts from both accounts
        $numContacts = $account->getAccountContacts()->count() + $acc->getAccountContacts()->count();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/multipledeleteinfo',
            [
                'ids' => [$account->getId(), $acc->getId()],
            ]
        );

        // asserts

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(1, $response->numChildren);
    }

    /**
     * Test if deleteinfo returns correct data.
     */
    public function testGetDeleteInfoById(): void
    {
        $account = $this->createAccount('Company');

        for ($i = 0; $i < 5; ++$i) {
            $contact = new Contact();
            $contact->setFirstName('Vorname ' . $i);
            $contact->setLastName('Nachname ' . $i);
            $contact->setMiddleName('Mittelname ' . $i);
            $contact->setFormOfAddress(0);
            $this->em->persist($contact);

            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($account);
            $accountContact->setMain(true);
            $this->em->persist($accountContact);
            $account->addAccountContact($accountContact);
        }

        $this->em->flush();
        $this->em->clear();

        $numContacts = $account->getAccountContacts()->count();

        $this->client->jsonRequest('GET', '/api/accounts/' . $account->getId() . '/deleteinfo');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());

        // number of returned contacts has to be less or equal 3
        $this->assertEquals(3, \count($response->contacts));

        // return full number of contacts related to account
        $this->assertEquals($numContacts, $response->numContacts);

        // allowed if no subaccount exists
        $this->assertEquals(0, $response->numChildren);
    }

    /**
     * Test if delete info returns right isAllowed, when there is a superaccount.
     */
    public function testGetDeleteInfoByIdWithSuperAccount(): void
    {
        $account = $this->createAccount('Parent');

        // changing test data: adding child accounts
        for ($i = 0; $i < 5; ++$i) {
            $childAccount = new Account();
            $childAccount->setName('child num#' . $i);
            $childAccount->setParent($account);
            $account->addChildren($childAccount);

            $this->em->persist($childAccount);
        }
        $this->em->flush();
        $this->em->clear();

        $accountId = $account->getId();

        $this->client->jsonRequest('GET', '/api/accounts/' . $accountId . '/deleteinfo');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        // deletion not allowed if children existent
        $this->assertGreaterThan(0, $response->numChildren);

        // number of returned contacts has to be less or equal 3
        $this->assertLessThanOrEqual(3, \count($response->children));
    }

    public function testGetDeleteInfoByIdNotExisting(): void
    {
        $this->client->jsonRequest('GET', '/api/accounts/4711/deleteinfo');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutRemovedParentAccount(): void
    {
        $urlType = $this->createUrlType('Private');
        $emailType = $this->createEmailType('Private');
        $phoneType = $this->createPhoneType('Private');
        $faxType = $this->createFaxType('Private');
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Company', null);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $account->getId()],
                'contactDetails' => [
                    'urls' => [
                        [
                            'url' => 'http://example.company.com',
                            'urlType' => $urlType->getId(),
                        ],
                    ],
                    'emails' => [
                        [
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'fax' => '123456789-1',
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
                'notes' => [
                    ['value' => 'Note 1'],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('ExampleCompany', $response->name);
        $this->assertEquals($account->getId(), $response->parent->id);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('123456789-1', $response->contactDetails->faxes[0]->fax);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);

        $account2Id = $response->id;

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account2Id,
            [
                'id' => $account2Id,
                'name' => 'ExampleCompany 222',
                'parent' => ['id' => null],
                'contactDetails' => [
                    'urls' => [
                        [
                            'url' => 'http://example.company.com',
                            'urlType' => $urlType->getId(),
                        ],
                    ],
                    'emails' => [
                        [
                            'id' => $response->contactDetails->emails[0]->id,
                            'email' => 'erika.mustermann@muster.at',
                            'emailType' => $emailType->getId(),
                        ],
                    ],
                    'phones' => [
                        [
                            'id' => $response->contactDetails->phones[0]->id,
                            'phone' => '123456789',
                            'phoneType' => $phoneType->getId(),
                        ],
                    ],
                    'faxes' => [
                        [
                            'id' => $response->contactDetails->faxes[0]->id,
                            'fax' => '123456789-1',
                            'faxType' => $faxType->getId(),
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $response->addresses[0]->id,
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
                'notes' => [
                    ['id' => $response->notes[0]->id, 'value' => 'Note 1'],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('ExampleCompany 222', $response->name);
        $this->assertNull($response->parent);
        $this->assertEquals('erika.mustermann@muster.at', $response->contactDetails->emails[0]->email);
        $this->assertEquals('123456789', $response->contactDetails->phones[0]->phone);
        $this->assertEquals('Musterstraße', $response->addresses[0]->street);
        $this->assertEquals('1', $response->addresses[0]->number);
        $this->assertEquals('0000', $response->addresses[0]->zip);
        $this->assertEquals('Musterstadt', $response->addresses[0]->city);
        $this->assertEquals('ML', $response->addresses[0]->countryCode);
        $this->assertEquals('Musterstate', $response->addresses[0]->state);
        $this->assertEquals('Note 1', $response->notes[0]->value);

        $this->assertEquals(true, $response->addresses[0]->billingAddress);
        $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[0]->deliveryAddress);
        $this->assertEquals('Dornbirn', $response->addresses[0]->postboxCity);
        $this->assertEquals('6850', $response->addresses[0]->postboxPostcode);
        $this->assertEquals('4711', $response->addresses[0]->postboxNumber);
    }

    public function testPrimaryAddressHandlingPost(): void
    {
        $urlType = $this->createUrlType('Private');
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $account = $this->createAccount('Company', null);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'POST',
            '/api/accounts',
            [
                'name' => 'ExampleCompany',
                'parent' => ['id' => $account->getId()],
                'urls' => [
                    [
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'erika.mustermann@muster.at',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'street' => 'Musterstraße',
                        'number' => '1',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Musterstraße',
                        'number' => '2',
                        'zip' => '0000',
                        'city' => 'Musterstadt',
                        'state' => 'Musterstate',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(true, $response->addresses[1]->primaryAddress);

        $this->client->jsonRequest('GET', '/api/accounts/' . $response->id);
        $response = \json_decode($this->client->getResponse()->getContent());

        if (1 == $response->addresses[0]->number) {
            $this->assertEquals(false, $response->addresses[0]->primaryAddress);
            $this->assertEquals(true, $response->addresses[1]->primaryAddress);
        } else {
            $this->assertEquals(false, $response->addresses[1]->primaryAddress);
            $this->assertEquals(true, $response->addresses[0]->primaryAddress);
        }
    }

    public function testPrimaryAddressHandlingPut(): void
    {
        $urlType = $this->createUrlType('Private');
        $url = $this->createUrl('http://www.company.example', $urlType);
        $emailType = $this->createEmailType('Private');
        $addressType = $this->createAddressType('Private');
        $address = $this->createAddress($addressType);
        $account = $this->createAccount('Company', null, $url, $address);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/accounts/' . $account->getId(),
            [
                'name' => 'ExampleCompany',
                'urls' => [
                    [
                        'id' => $url->getId(),
                        'url' => 'http://example.company.com',
                        'urlType' => [
                            'id' => $urlType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'emails' => [
                    [
                        'email' => 'office@company.com',
                        'emailType' => [
                            'id' => $emailType->getId(),
                            'name' => 'Private',
                        ],
                    ],
                ],
                'addresses' => [
                    [
                        'id' => $address->getId(),
                        'street' => 'Bahnhofstraße',
                        'number' => '2',
                        'zip' => '0022',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Rathausgasse 1',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                    [
                        'street' => 'Rathausgasse 2',
                        'number' => '3',
                        'zip' => '2222',
                        'city' => 'Dornbirn',
                        'state' => 'state1',
                        'countryCode' => 'ML',
                        'addressType' => $addressType->getId(),
                        'billingAddress' => true,
                        'primaryAddress' => true,
                        'deliveryAddress' => false,
                        'postboxCity' => 'Dornbirn',
                        'postboxPostcode' => '6850',
                        'postboxNumber' => '4711',
                    ],
                ],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        \usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);

        $this->client->jsonRequest(
            'GET',
            '/api/accounts/' . $account->getId()
        );

        $response = \json_decode((string) $this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        \usort($response->addresses, $this->sortAddressesPrimaryLast());

        $this->assertEquals(false, $response->addresses[0]->primaryAddress);
        $this->assertEquals(false, $response->addresses[1]->primaryAddress);
        $this->assertEquals(true, $response->addresses[2]->primaryAddress);
    }

    public function sortAddressesPrimaryLast()
    {
        return function($a, $b) {
            if (true === $a->primaryAddress && false === $b->primaryAddress) {
                return true;
            }

            return false;
        };
    }

    public function testGetAccountsWithNoParent(): void
    {
        $this->createAccount('Account 1');
        $account2 = $this->createAccount('Account 2');
        $this->createAccount('Account 2.1', $account2);
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/accounts?flat=true&hasNoParent=true'
        );

        $response = \json_decode((string) $this->client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(2, $response->total);
    }

    /**
     * Creates a minimal account.
     *
     * @return AccountInterface
     */
    private function createAccount(
        string $name,
        ?Account $parent = null,
        ?Url $url = null,
        ?Address $address = null,
        ?Email $email = null,
        ?Phone $phone = null,
        ?Fax $fax = null,
        ?Note $note = null,
        ?string $placeOfJurisdiction = null,
        ?Media $logo = null,
        ?array $categories = null,
        ?Contact $mainContact = null
    ) {
        $account = new Account();
        $account->setName($name);
        $account->setParent($parent);

        if ($placeOfJurisdiction) {
            $account->setPlaceOfJurisdiction($placeOfJurisdiction);
        }

        if ($url) {
            $account->addUrl($url);
        }

        if ($logo) {
            $account->setLogo($logo);
        }

        if ($address) {
            $accountAddress = new AccountAddress();
            $accountAddress->setAddress($address);
            $accountAddress->setAccount($account);
            $accountAddress->setMain(true);
            $account->addAccountAddress($accountAddress);
            $this->em->persist($accountAddress);
        }

        if ($email) {
            $account->addEmail($email);
        }

        if ($phone) {
            $account->addPhone($phone);
        }

        if ($fax) {
            $account->addFax($fax);
        }

        if ($note) {
            $account->addNote($note);
        }

        if ($categories) {
            foreach ($categories as $category) {
                $account->addCategory($category);
            }
        }

        if ($mainContact) {
            $account->setMainContact($mainContact);
        }

        $this->em->persist($account);

        return $account;
    }

    private function createUrlType(string $name)
    {
        $urlType = new UrlType();
        $urlType->setName($name);

        $this->em->persist($urlType);

        return $urlType;
    }

    private function createUrl(string $urlValue, UrlType $urlType)
    {
        $url = new Url();
        $url->setUrl($urlValue);
        $url->setUrlType($urlType);

        $this->em->persist($url);

        return $url;
    }

    private function createEmail(string $emailAddress, EmailType $emailType)
    {
        $email = new Email();
        $email->setEmail($emailAddress);
        $email->setEmailType($emailType);

        $this->em->persist($email);

        return $email;
    }

    private function createEmailType(string $type)
    {
        $emailType = new EmailType();
        $emailType->setName($type);

        $this->em->persist($emailType);

        return $emailType;
    }

    private function createAddressType(string $type)
    {
        $addressType = new AddressType();
        $addressType->setName($type);

        $this->em->persist($addressType);

        return $addressType;
    }

    private function createFaxType(string $type)
    {
        $faxType = new FaxType();
        $faxType->setName('Private');

        $this->em->persist($faxType);

        return $faxType;
    }

    private function createFax(string $number, FaxType $faxType)
    {
        $fax = new Fax();
        $fax->setFax('123654789');
        $fax->setFaxType($faxType);

        $this->em->persist($fax);

        return $fax;
    }

    private function createPhoneType(string $type)
    {
        $phoneType = new PhoneType();
        $phoneType->setName($type);

        $this->em->persist($phoneType);

        return $phoneType;
    }

    private function createPhone(string $phoneNumber, PhoneType $phoneType)
    {
        $phone = new Phone();
        $phone->setPhone($phoneNumber);
        $phone->setPhoneType($phoneType);

        $this->em->persist($phone);

        return $phone;
    }

    private function createPosition(string $positionName)
    {
        $position = new Position();
        $position->setPosition($positionName);

        $this->em->persist($position);

        return $position;
    }

    private function createAddress(
        ?AddressType $addressType = null,
        ?string $street = null,
        ?string $number = null,
        ?string $zip = null,
        ?string $city = null,
        ?string $state = null,
        ?string $countryCode = null,
        ?bool $billingAddress = null,
        ?bool $primaryAddress = null,
        ?bool $deliveryAddress = null,
        ?string $postboxCity = null,
        ?string $postboxCode = null,
        ?string $postboxNumber = null,
        ?string $note = null,
        ?float $latitude = null,
        ?float $longitude = null
    ) {
        $address = new Address();
        $address->setStreet($street);
        $address->setNumber($number);
        $address->setZip($zip);
        $address->setCity($city);
        $address->setState($state);
        $address->setCountryCode($countryCode);
        $address->setAddition('');
        $address->setAddressType($addressType);
        $address->setBillingAddress($billingAddress);
        $address->setPrimaryAddress($primaryAddress);
        $address->setDeliveryAddress($deliveryAddress);
        $address->setPostboxCity($postboxCity);
        $address->setPostboxPostcode($postboxCode);
        $address->setPostboxNumber($postboxNumber);
        $address->setNote($note);
        $address->setLatitude($latitude);
        $address->setLongitude($longitude);

        $this->em->persist($address);

        return $address;
    }

    private function createContact(
        ?Account $account,
        string $firstName,
        string $lastName,
        ?string $middleName = null,
        ?int $formOfAddress = null
    ) {
        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->setMiddleName($middleName);
        $contact->setFormOfAddress($formOfAddress);

        if ($account) {
            $accountContact = new AccountContact();
            $accountContact->setContact($contact);
            $accountContact->setAccount($account);
            $accountContact->setMain(true);
            $account->addAccountContact($accountContact);
            $this->em->persist($accountContact);
        }

        $this->em->persist($contact);

        return $contact;
    }

    private function createCollection(CollectionType $collectionType)
    {
        $collection = new Collection();
        $collection->setType($collectionType);
        $this->em->persist($collection);

        return $collection;
    }

    private function createCollectionType(string $name)
    {
        $collectionType = new CollectionType();
        $collectionType->setName($name);
        $this->em->persist($collectionType);

        return $collectionType;
    }

    private function createMediaType(string $name, ?string $description = null)
    {
        $mediaType = new MediaType();
        $mediaType->setName($name);
        $mediaType->setDescription($description);
        $this->em->persist($mediaType);

        return $mediaType;
    }

    private function createMedia(string $name, string $mimeType, MediaType $mediaType, Collection $collection)
    {
        $file = new File();
        $file->setVersion(1);

        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName('media1.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(111111);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1950-04-20'));
        $fileVersion->setCreated(new \DateTime('1950-04-20'));
        $file->addFileVersion($fileVersion);
        $this->em->persist($fileVersion);

        $media = new Media();
        $media->setType($mediaType);
        $media->setCollection($collection);
        $media->addFile($file);
        $file->setMedia($media);
        $this->em->persist($media);
        $this->em->persist($file);

        return $media;
    }

    private function createNote(string $value)
    {
        $note = new Note();
        $note->setValue($value);

        $this->em->persist($note);

        return $note;
    }

    /**
     * Creates a certain amount of accounts.
     *
     * @param int $number
     *
     * @return array
     */
    private function createMultipleMinimalAccounts($number)
    {
        $accounts = [];

        for ($i = 0; $i < $number; ++$i) {
            $accounts[] = $this->createAccount('Minimal Account ' . $this->accountCount++);
        }

        return $accounts;
    }

    private function createCategory(string $key, string $locale, string $name, string $description)
    {
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey($name);
        $category->setDefaultLocale($locale);

        // name for first category
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale($locale);
        $categoryTrans->setTranslation($name);
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale($locale);
        $categoryMeta->setKey('description');
        $categoryMeta->setValue($description);
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        $this->em->persist($category);

        return $category;
    }
}
