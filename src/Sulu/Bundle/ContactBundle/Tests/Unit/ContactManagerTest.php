<?php

declare(strict_types=1);

namespace Sulu\Bundle\ContactBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;

class ContactManagerTest extends TestCase
{
    /**
     * @var ContactManager
     */
    private $contactManager;

    /**
     * @var ObjectManager
     */
    private $em;
    /**
     * @var TagManagerInterface
     */
    private $tagManager;
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;
    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;
    /**
     * @var ContactTitleRepository
     */
    private $contactTitleRepository;
    /**
     * @var ContactRepository
     */
    private $contactRepository;
    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    protected function setUp()
    {
        $this->em = $this->prophesize(ObjectManager::class);
        $this->tagManager = $this->prophesize(TagManagerInterface::class);
        $this->mediaManager = $this->prophesize(MediaManagerInterface::class);
        $this->accountRepository = $this->prophesize(AccountRepositoryInterface::class);
        $this->contactTitleRepository = $this->prophesize(ContactTitleRepository::class);
        $this->contactRepository = $this->prophesize(ContactRepository::class);
        $this->mediaRepository = $this->prophesize(MediaRepositoryInterface::class);

        $this->contactManager = new ContactManager($this->em->reveal(), $this->tagManager->reveal(), $this->mediaManager->reveal(), $this->accountRepository->reveal(), $this->contactTitleRepository->reveal(), $this->contactRepository->reveal(), $this->mediaRepository->reveal());
    }

    public function testAddTag()
    {
        /** @var Contact $contact */
        $contact = $this->prophesize(Contact::class);
        $tag = $this->prophesize(TagInterface::class);

        $contact->getContactAddresses()->willReturn([]);
        $contact->getTags()->willReturn([]);
        $this->tagManager->findOrCreateByName('testtag')->willReturn($tag->reveal());
        $contact->addTag($tag->reveal())->shouldBeCalled();

        $this->contactManager->addNewContactRelations($contact->reveal(), ['tags' => ['testtag']]);
    }
}
