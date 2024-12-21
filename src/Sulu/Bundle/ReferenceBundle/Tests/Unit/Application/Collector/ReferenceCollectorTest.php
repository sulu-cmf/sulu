<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Application\Collector;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Document\WorkflowStage;

class ReferenceCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ReferenceRepositoryInterface>
     */
    private ObjectProphecy $referenceRepository;

    protected function setUp(): void
    {
        $this->referenceRepository = $this->prophesize(ReferenceRepositoryInterface::class);
        $this->referenceRepository->create(Argument::cetera())
            ->will(function($args) {
                /** @var string $resourceKey */
                $resourceKey = $args[0];
                /** @var string $resourceId */
                $resourceId = $args[1];
                /** @var string $referenceResourceKey */
                $referenceResourceKey = $args[2];
                /** @var string $referenceResourceId */
                $referenceResourceId = $args[3];
                /** @var string $referenceLocale */
                $referenceLocale = $args[4];
                /** @var string $referenceTitle */
                $referenceTitle = $args[5];
                /** @var string $referenceContext */
                $referenceContext = $args[6];
                /** @var string $referenceProperty */
                $referenceProperty = $args[7];
                /** @var array<string, string> $referenceRouterAttributes */
                $referenceRouterAttributes = $args[8] ?? [];

                $reference = new Reference();

                $reference
                    ->setResourceKey($resourceKey)
                    ->setResourceId($resourceId)
                    ->setReferenceLocale($referenceLocale)
                    ->setReferenceResourceKey($referenceResourceKey)
                    ->setReferenceResourceId($referenceResourceId)
                    ->setReferenceTitle($referenceTitle)
                    ->setReferenceContext($referenceContext)
                    ->setReferenceProperty($referenceProperty)
                    ->setReferenceRouterAttributes($referenceRouterAttributes);

                return $reference;
            });
    }

    public function testAddReference(): void
    {
        $referenceCollector = $this->createReferenceCollector(
            referenceRouterAttributes: [
                'webspace' => 'sulu',
            ],
            referenceWorkflowStage: WorkflowStage::PUBLISHED,
        );
        $reference = $referenceCollector->addReference('media', '1', 'headerImage');

        $this->assertSame('media', $reference->getResourceKey());
        $this->assertSame('1', $reference->getResourceId());
        $this->assertSame('pages', $reference->getReferenceResourceKey());
        $this->assertSame('79041d83-8229-472d-9ada-01c50915de1e', $reference->getReferenceResourceId());
        $this->assertSame('en', $reference->getReferenceLocale());
        $this->assertSame('Title', $reference->getReferenceTitle());
        $this->assertSame('default', $reference->getReferenceContext());
        $this->assertSame('headerImage', $reference->getReferenceProperty());
    }

    public function testAddReferenceSame(): void
    {
        $referenceCollector = $this->createReferenceCollector();
        $referenceA = $referenceCollector->addReference('media', '1', 'headerImage');
        $referenceB = $referenceCollector->addReference('media', '1', 'headerImage');

        $this->assertSame($referenceA, $referenceB);
    }

    public function testPersistReferences(): void
    {
        $referenceCollector = $this->createReferenceCollector();
        $reference1 = $referenceCollector->addReference('media', '1', 'headerImage');
        $reference2 = $referenceCollector->addReference('media', '1', 'headerImage');

        $this->referenceRepository->removeBy([
            'referenceResourceKey' => 'pages',
            'referenceResourceId' => '79041d83-8229-472d-9ada-01c50915de1e',
            'referenceContext' => 'default',
            'referenceLocale' => 'en',
        ])->shouldBeCalled();

        $this->referenceRepository->add($reference1)->shouldBeCalled();
        $this->referenceRepository->add($reference2)->shouldBeCalled();

        $referenceCollector->persistReferences();
    }

    /**
     * @param array<string, string> $referenceRouterAttributes
     */
    public function createReferenceCollector(
        string $referenceResourceKey = 'pages',
        string $referenceResourceId = '79041d83-8229-472d-9ada-01c50915de1e',
        string $referenceLocale = 'en',
        string $referenceTitle = 'Title',
        string $referenceContext = 'default',
        array $referenceRouterAttributes = [],
        ?int $referenceWorkflowStage = null
    ): ReferenceCollector {
        return new ReferenceCollector(
            $this->referenceRepository->reveal(),
            $referenceResourceKey,
            $referenceResourceId,
            $referenceLocale,
            $referenceTitle,
            $referenceContext,
            $referenceRouterAttributes,
        );
    }
}
