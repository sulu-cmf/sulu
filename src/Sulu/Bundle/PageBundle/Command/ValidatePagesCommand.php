<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Command;

use Jackalope\Query\Row;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProviderInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:content:validate', description: 'Dumps pages without valid templates')]
class ValidatePagesCommand extends Command
{
    public function __construct(
        private SessionInterface $session,
        private WebspaceManagerInterface $webspaceManager,
        private StructureManagerInterface $structureManager,
        private WebspaceStructureProviderInterface $structureProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('webspaceKey', InputArgument::REQUIRED, 'Which webspace to search');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $webspaceKey = $input->getArgument('webspaceKey');

        /** @var Webspace $webspace */
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        $select = '';
        $headers = [];
        foreach ($webspace->getAllLocalizations() as $localization) {
            $select .= '[i18n:' . $localization->getLocale() . '-template] as ' . $localization->getLocale() . ',';
            $headers[] = $localization->getLocale();
        }
        $select = \rtrim($select, ',');

        $sql2 = \sprintf(
            "SELECT %s FROM [nt:unstructured] as page WHERE page.[jcr:mixinTypes] = 'sulu:page' AND (isdescendantnode(page, '/cmf/%s/contents') OR issamenode(page, '/cmf/%s/contents'))",
            $select,
            $webspaceKey,
            $webspaceKey
        );

        $structures = [];
        foreach ($this->structureManager->getStructures() as $structure) {
            $structures[] = $structure->getKey();
        }

        $availableStructureKeys = [];
        foreach ($this->structureProvider->getStructures($webspaceKey) as $structure) {
            $availableStructureKeys[] = $structure->getKey();
        }

        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $queryResult = $query->execute();

        $completeHeader = \array_merge(['invalid', 'path'], $headers, ['description']);

        $table = new Table($output);
        $table->setHeaders($completeHeader);
        $result = 0;
        $messages = [];

        /** @var Row $row */
        foreach ($queryResult as $row) {
            $tableRow = [' '];

            $tableRow[] = $row->getPath();
            $descriptions = [];

            foreach ($headers as $header) {
                $template = $row->getValue($header);
                $tableRow[] = $template;
                if ('' !== $template && !\in_array($template, $structures)) {
                    $tableRow[0] = 'X';
                    $descriptions[] = \sprintf('Language "%s" contains a not existing xml-template', $header);
                    ++$result;
                }
                if ('' !== $template && !\in_array($template, $availableStructureKeys)) {
                    $tableRow[0] = 'X';
                    $descriptions[] = \sprintf(
                        'Language "%s" contains a not implemented xml-template in webspace "%s"',
                        $header,
                        $webspaceKey
                    );
                    ++$result;
                }
            }

            $messages = \array_merge($messages, $descriptions);
            $tableRow[] = \implode(', ', $descriptions);

            $table->addRow($tableRow);
        }
        $table->render();

        $style = new OutputFormatterStyle('red', null, ['bold', 'blink']);
        $output->getFormatter()->setStyle('error', $style);

        $style = new OutputFormatterStyle('green', null, ['bold', 'blink']);
        $output->getFormatter()->setStyle('ok', $style);

        $output->writeln('');

        if ($result > 0) {
            $output->writeln(\sprintf("<error>%s Errors found: \r\n  - %s</error>", $result, \implode("\r\n  - ", $messages)));
        } else {
            $output->writeln(\sprintf('<ok>%s Errors found</ok>', $result));
        }

        return 0;
    }
}
