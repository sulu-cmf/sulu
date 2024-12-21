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

namespace Sulu\Bundle\AdminBundle\Command;

use Sulu\Bundle\AdminBundle\Admin\View\DropdownToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\FormOverlayListViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceTabViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: 'sulu:admin:debug-view', description: 'Display current Views for the admin')]
class ViewDebugCommand extends Command
{
    public function __construct(
        private ViewRegistry $viewRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::OPTIONAL, 'A view name'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $views = $this->viewRegistry->getViews();
        $name = (string) $input->getArgument('name');

        $view = null;
        if ($name) {
            $matchingViews = $this->findViewNameContaining($name, $views);
            $default = 1 === \count($matchingViews) ? $matchingViews[0] : null;

            if ($default !== $name) {
                $name = $io->choice('Select one of the matching Views', $matchingViews, $default);
            }
            $view = $this->viewRegistry->findViewByName($name);

            if (!$view) {
                throw new InvalidArgumentException(\sprintf('The View "%s" does not exist.', $name));
            }

            $tableRows = [];
            $tableRows[] = ['Name', $view->getName()];
            $tableRows[] = ['Type', $view->getType()];
            $tableRows[] = ['Path', $view->getPath()];

            $tableRows = match ($view->getType()) {
                ResourceTabViewBuilder::TYPE => $this->handleResourceTab($view, $tableRows),
                ListViewBuilder::TYPE, FormOverlayListViewBuilder::TYPE => $this->handleList($view, $tableRows),
                FormViewBuilder::TYPE, PreviewFormViewBuilder::TYPE => $this->handleFormView($view, $tableRows),
                default => $this->default($view, $tableRows),
            };

            $table = new Table($output);
            $table
                ->setHeaders(['Property', 'Value'])
                ->setRows($tableRows);
            $table->render();

            return Command::SUCCESS;
        }

        $tableRows = [];
        foreach ($views as $view) {
            $row = [$view->getName(), $view->getType(), $view->getPath()];
            $tableRows[] = $row;
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'Type', 'Path'])
            ->setRows($tableRows);
        $table->render();

        return Command::SUCCESS;
    }

    private function findViewNameContaining(string $name, array $views): array
    {
        $foundViewsNames = [];
        foreach ($views as $view) {
            if (false !== \stripos($view->getName(), $name)) {
                $foundViewsNames[] = $view->getName();
            }
        }

        return $foundViewsNames;
    }

    private function handleResourceTab(View $view, array $tableRows): array
    {
        $tableRows[] = ['resourceKey', $view->getOption('resourceKey')];
        $tableRows[] = ['backView', $view->getOption('backView')];
        $tableRows[] = ['titleProperty', $view->getOption('titleProperty')];

        return $tableRows;
    }

    private function handleList(View $view, array $tableRows): array
    {
        $tableRows[] = ['resourceKey', $view->getOption('resourceKey')];
        $tableRows[] = ['listKey', $view->getOption('listKey')];
        $adapters = \implode('', $view->getOption('adapters'));
        $tableRows[] = ['adapters', $adapters];

        $tableRows[] = ['addView', $view->getOption('addView')];
        $tableRows[] = ['editView', $view->getOption('editView')];

        $tableRows[] = ['toolbarActions', ''];
        foreach ($view->getOption('toolbarActions') as $action) {
            $tableRows[] = ['', $action->getType()];
        }

        return $tableRows;
    }

    private function handleFormView(View $view, array $tableRows): array
    {
        $tableRows[] = ['resourceKey', $view->getOption('resourceKey')];
        $tableRows[] = ['formKey', $view->getOption('formKey')];
        $tableRows[] = ['tabTitle', $view->getOption('tabTitle')];
        $tableRows[] = ['editView', $view->getOption('editView')];

        $tableRows[] = ['toolbarActions', ''];
        /** @var DropdownToolbarAction $action */
        foreach ($view->getOption('toolbarActions') as $action) {
            $tableRows[] = ['', $action->getType()];
        }

        return $tableRows;
    }

    private function default(View $view, array $tableRows): array
    {
        return $tableRows;
    }
}
