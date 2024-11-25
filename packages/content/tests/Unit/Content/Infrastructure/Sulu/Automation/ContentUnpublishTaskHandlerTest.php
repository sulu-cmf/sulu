<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Automation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Infrastructure\Sulu\Automation\ContentUnpublishTaskHandler;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentUnpublishTaskHandlerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    /**
     * @var ObjectProphecy<EntityManagerInterface>
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<ObjectRepository<Example>>
     */
    private $repository;

    /**
     * @var ObjectProphecy<ContentManagerInterface>
     */
    private $contentManager;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var ContentUnpublishTaskHandler
     */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->repository = $this->prophesize(ObjectRepository::class); // @phpstan-ignore-line
        $this->contentManager = $this->prophesize(ContentManagerInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->handler = new ContentUnpublishTaskHandler($this->contentManager->reveal(), $this->entityManager->reveal(), $this->translator->reveal());
    }

    public function testUnpublish(): void
    {
        $entity = new Example();

        $class = ContentRichEntityInterface::class;
        $id = 1;
        $locale = 'en';

        $this->entityManager->getRepository(Argument::is($class))
            ->willReturn($this->repository->reveal())
            ->shouldBeCalled();

        $this->repository->findOneBy(Argument::is(['id' => $id]))
            ->willReturn($entity)
            ->shouldBeCalled();

        $this->contentManager->applyTransition(
            Argument::is($entity),
            Argument::is(['locale' => $locale]),
            Argument::is(WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH)
        )->shouldBeCalled();

        $this->entityManager->flush()->shouldBeCalled();

        $this->handler->handle([
            'class' => $class,
            'id' => $id,
            'locale' => $locale,
        ]);
    }

    public function testConfiguration(): void
    {
        $this->translator->trans(Argument::type('string'), Argument::type('array'), Argument::exact('admin'))->willReturn('Unpublish');

        $configuration = $this->handler->getConfiguration();

        $this->assertSame('Unpublish', $configuration->getTitle());
    }

    public function testSupports(): void
    {
        $entity = new Example();

        $this->assertTrue($this->handler->supports($entity::class));
        $this->assertFalse($this->handler->supports(PageDocument::class));
    }

    public function testConfigureOptionsResolver(): void
    {
        $optionsResolver = new OptionsResolver();

        $result = $this->handler->configureOptionsResolver($optionsResolver);
        $this->assertSame($optionsResolver, $result);
        $this->assertSame(['id', 'locale'], $optionsResolver->getRequiredOptions());
    }
}
