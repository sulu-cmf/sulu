<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Command;

use Jackalope\Query\Row;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('sulu:search:reindex-content');
        $this->setDescription('Reindex the content in the search index');
        $this->setHelp(
            <<<EOT
            The %command.name_full% command will retindex all the sulu Structures in search index.
EOT
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var SessionInterface $session */
        $session = $container->get('sulu.phpcr.session')->getSession();

        /** @var ContentMapperInterface $contentMapper */
        $contentMapper = $container->get('sulu.content.mapper');

        /** @var SearchManagerInterface $searchManager */
        $searchManager = $container->get('massive_search.search_manager');

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $container->get('sulu_core.webspace.webspace_manager');

        // path parts
        $webspacePrefix = $container->getParameter('sulu.content.node_names.base');

        $sql2 = 'SELECT * FROM [nt:unstructured] AS a WHERE [jcr:mixinTypes] = "sulu:page"';

        $queryManager = $session->getWorkspace()->getQueryManager();

        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');

        $res = $query->execute();

        /** @var SuluNodeHelper $nodeHelper */
        $nodeHelper = $container->get('sulu.util.node_helper');

        /** @var Row $row */
        foreach ($res->getRows() as $row) {
            $node = $row->getNode('a');

            $locales = $nodeHelper->getLanguagesForNode($node);
            foreach ($locales as $locale) {

                // Evil: Should be encapsulated.
                if (!preg_match('{/' . $webspacePrefix . '/(.*?)/(.*?)(/.*)*$}', $node->getPath(), $matches)) {
                    $output->writeln(
                        sprintf('<error> - Could not determine webspace for </error>: %s', $node->getPath())
                    );
                    continue;
                }
                $webspaceKey = $matches[1];

                if ($webspaceManager->findWebspaceByKey($webspaceKey) !== null) {
                    $structure = $contentMapper->load($node->getIdentifier(), $webspaceKey, $locale);

                    try {
                        if ($structure->getNodeState() === Structure::STATE_PUBLISHED) {
                            $output->writeln(
                                '  [+] <comment>Indexing published page (locale: ' . $locale . ')</comment>: ' .
                                $node->getPath()
                            );
                            $searchManager->index($structure, $locale);
                        } else {
                            $output->writeln(
                                '  [-] <comment>De-indexing unpublished page (locale: ' . $locale . ')</comment>: ' .
                                $node->getPath()
                            );
                            $searchManager->deindex($structure, $locale);
                        }
                    } catch (\Exception $exc) {
                        $output->writeln(
                            '  [!] <error>Error indexing or de-indexing page (path: ' . $node->getPath() .
                            ', locale: ' . $locale . '): ' . $exc->getMessage() . '</error>'
                        );
                    }
                }
            }
        }
    }
}
