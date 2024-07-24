<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Import;

use Symfony\Component\Console\Output\OutputInterface;

interface ArticleImportInterface
{
    public function import(
        string $locale,
        string $filePath,
        ?OutputInterface $output = null,
        string $format = '1.2.xliff',
        bool $overrideSettings = false
    ): ImportResult;
}
