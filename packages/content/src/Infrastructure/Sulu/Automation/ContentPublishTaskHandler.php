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

namespace Sulu\Content\Infrastructure\Sulu\Automation;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\TaskHandlerConfiguration;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentPublishTaskHandler implements AutomationTaskHandlerInterface
{
    public function __construct(
        private ContentManagerInterface $contentManager,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function configureOptionsResolver(OptionsResolver $optionsResolver): OptionsResolver
    {
        return $optionsResolver->setRequired(['id', 'locale'])
            ->setAllowedTypes('id', 'string')
            ->setAllowedTypes('locale', 'string');
    }

    public function supports(string $entityClass): bool
    {
        return \is_subclass_of($entityClass, ContentRichEntityInterface::class);
    }

    public function getConfiguration(): TaskHandlerConfiguration
    {
        return TaskHandlerConfiguration::create($this->translator->trans('sulu_content.task_handler.publish', [], 'admin'));
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param array{
     *     class: class-string<ContentRichEntityInterface<T>>,
     *     id: int|string,
     *     locale: string,
     * } $workload
     */
    public function handle($workload)
    {
        if (!\is_array($workload)) {
            // TODO FIXME add test case for this
            return; // @codeCoverageIgnore
        }

        $class = $workload['class'];
        $repository = $this->entityManager->getRepository($class);

        $entity = $repository->findOneBy(['id' => $workload['id']]);
        if (null === $entity) {
            // TODO FIXME add test case for this
            return; // @codeCoverageIgnore
        }

        $this->contentManager->applyTransition(
            $entity,
            ['locale' => $workload['locale']],
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
        );
        $this->entityManager->flush();
    }
}
