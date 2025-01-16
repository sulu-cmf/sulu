<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\AddressType;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\ContactBundle\Entity\FaxType;
use Sulu\Bundle\ContactBundle\Entity\PhoneType;
use Sulu\Bundle\ContactBundle\Entity\SocialMediaProfileType;
use Sulu\Bundle\ContactBundle\Entity\UrlType;

/**
 * @internal This is an internal class which should not be used by a project.
 *           Instead Create your own fixtures file instead.
 */
class LoadDefaultTypes extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Phone types.
        $metadata = $manager->getClassMetaData(PhoneType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $phoneType1 = $manager->find(PhoneType::class, 1);
        if (null === $phoneType1) {
            $phoneType1 = new PhoneType();
            $phoneType1->setId(1);
            $manager->persist($phoneType1);
            $phoneType1->setName('sulu_contact.work');
        }

        $phoneType2 = $manager->find(PhoneType::class, 2);
        if (null === $phoneType2) {
            $phoneType2 = new PhoneType();
            $phoneType2->setId(2);
            $manager->persist($phoneType2);
            $phoneType2->setName('sulu_contact.private');
        }

        $phoneType3 = $manager->find(PhoneType::class, 3);
        if (null === $phoneType3) {
            $phoneType3 = new PhoneType();
            $phoneType3->setId(3);
            $manager->persist($phoneType3);
            $phoneType3->setName('sulu_contact.mobile');
        }

        // Email types.
        $metadata = $manager->getClassMetaData(EmailType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $emailType1 = $manager->find(EmailType::class, 1);
        if (null === $emailType1) {
            $emailType1 = new EmailType();
            $emailType1->setId(1);
            $manager->persist($emailType1);
            $emailType1->setName('sulu_contact.work');
        }

        $this->addReference('email.type.work', $emailType1);

        $emailType2 = $manager->find(EmailType::class, 2);
        if (null === $emailType2) {
            $emailType2 = new EmailType();
            $emailType2->setId(2);
            $manager->persist($emailType2);
            $emailType2->setName('sulu_contact.private');
        }

        $this->addReference('email.type.private', $emailType2);

        // Address types.
        $metadata = $manager->getClassMetaData(AddressType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $addressType1 = $manager->find(AddressType::class, 1);
        if (null === $addressType1) {
            $addressType1 = new AddressType();
            $addressType1->setId(1);
            $manager->persist($addressType1);
            $addressType1->setName('sulu_contact.work');
        }

        $addressType2 = $manager->find(AddressType::class, 2);
        if (null === $addressType2) {
            $addressType2 = new AddressType();
            $addressType2->setId(2);
            $manager->persist($addressType2);
            $addressType2->setName('sulu_contact.private');
        }

        // Url types.
        $metadata = $manager->getClassMetaData(UrlType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $urlType1 = $manager->find(UrlType::class, 1);
        if (null === $urlType1) {
            $urlType1 = new UrlType();
            $urlType1->setId(1);
            $manager->persist($urlType1);
            $urlType1->setName('sulu_contact.work');
        }

        $urlType2 = $manager->find(UrlType::class, 2);
        if (null === $urlType2) {
            $urlType2 = new UrlType();
            $urlType2->setId(2);
            $manager->persist($urlType2);
            $urlType2->setName('sulu_contact.private');
        }

        // Fax types.
        $metadata = $manager->getClassMetaData(FaxType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $faxType1 = $manager->find(FaxType::class, 1);
        if (null === $faxType1) {
            $faxType1 = new FaxType();
            $faxType1->setId(1);
            $manager->persist($faxType1);
            $faxType1->setName('sulu_contact.work');
        }

        $faxType2 = $manager->find(FaxType::class, 2);
        if (null === $faxType2) {
            $faxType2 = new FaxType();
            $faxType2->setId(2);
            $manager->persist($faxType2);
            $faxType2->setName('sulu_contact.private');
        }

        // Social media profile types.
        $metadata = $manager->getClassMetaData(SocialMediaProfileType::class);
        $metadata->setIdGenerator(new AssignedGenerator());
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $socialMediaProfileType1 = $manager->find(SocialMediaProfileType::class, 1);
        if (null === $socialMediaProfileType1) {
            $socialMediaProfileType1 = new SocialMediaProfileType();
            $socialMediaProfileType1->setId(1);
            $manager->persist($socialMediaProfileType1);
            $socialMediaProfileType1->setName('Facebook');
        }

        $socialMediaProfileType2 = $manager->find(SocialMediaProfileType::class, 2);
        if (null === $socialMediaProfileType2) {
            $socialMediaProfileType2 = new SocialMediaProfileType();
            $socialMediaProfileType2->setId(2);
            $manager->persist($socialMediaProfileType2);
            $socialMediaProfileType2->setName('Twitter');
        }

        $socialMediaProfileType3 = $manager->find(SocialMediaProfileType::class, 3);
        if (null === $socialMediaProfileType3) {
            $socialMediaProfileType3 = new SocialMediaProfileType();
            $socialMediaProfileType3->setId(3);
            $manager->persist($socialMediaProfileType3);
            $socialMediaProfileType3->setName('Instagram');
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}
