<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Export;

class ExportFormatNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $format;

    public function __construct(string $format, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('No format "%s" configured for Snippet export', $format), $code, $previous);

        $this->format = $format;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
