<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\TagsSubscriber;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class TagsSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TagsSubscriber
     */
    private $tagsSubscriber;

    /**
     * @var ObjectProphecy<ReferenceStorePoolInterface>
     */
    private $referenceStorePool;

    /**
     * @var ObjectProphecy<SymfonyResponseTagger>
     */
    private $symfonyResponseTagger;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var (ReferenceStoreInterface|ObjectProphecy)[]
     */
    private $referenceStores;

    /**
     * @var ObjectProphecy<StructureInterface>
     */
    private $structure;

    /**
     * @var string
     */
    private $uuid1;

    /**
     * @var string
     */
    private $uuid2;

    /**
     * @var string
     */
    private $currentStructureUuid;

    public function setUp(): void
    {
        $this->uuid1 = Uuid::v4()->toString();
        $this->uuid2 = Uuid::v4()->toString();
        $this->currentStructureUuid = Uuid::v4()->toString();

        $testReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $testReferenceStore->getAll()->willReturn(['1', '2']);
        $this->referenceStores['test'] = $testReferenceStore;

        $testReferenceStore2 = $this->prophesize(ReferenceStoreInterface::class);
        $testReferenceStore2->getAll()->willReturn([$this->uuid1, $this->uuid2]);
        $this->referenceStores['test_uuid'] = $testReferenceStore2;

        $this->referenceStorePool = $this->prophesize(ReferenceStorePoolInterface::class);
        $this->referenceStorePool->getStores()->willReturn($this->referenceStores);

        $this->symfonyResponseTagger = $this->prophesize(SymfonyResponseTagger::class);

        $this->structure = $this->prophesize(StructureInterface::class);
        $this->structure->getUuid()->willReturn($this->currentStructureUuid);

        $this->request = $this->prophesize(Request::class);
        $this->request->get('structure')->willReturn($this->structure->reveal());

        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestStack->getCurrentRequest()->willReturn($this->request);

        $this->tagsSubscriber = new TagsSubscriber(
            $this->referenceStorePool->reveal(),
            $this->symfonyResponseTagger->reveal(),
            $this->requestStack->reveal()
        );
    }

    public function testGet(): void
    {
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
            $this->currentStructureUuid,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetEmptyReferenceStore(): void
    {
        $this->referenceStores['test_uuid']->getAll()->willReturn([]);
        $expectedTags = [
            'test-1',
            'test-2',
            $this->currentStructureUuid,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithoutStructure(): void
    {
        $this->request->get('structure')->willReturn(null);
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithWrongStructure(): void
    {
        $this->request->get('structure')->willReturn(\stdClass::class);
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithoutRequest(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testEmptyReferenceStore(): void
    {
        $this->request->get('structure')->willReturn(null);
        $this->referenceStores['test_uuid']->getAll()->willReturn([]);
        $this->referenceStores['test']->getAll()->willReturn([]);
        $this->symfonyResponseTagger->addTags(Argument::any())->shouldNotBeCalled();
        $this->tagsSubscriber->addTags();
    }
}
