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

namespace Sulu\Bundle\MediaBundle\FileInspector;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * @internal
 */
final class SvgSanitizerFactory
{
    public function createSafe(): HtmlSanitizerInterface
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('svg', ['width', 'height', 'viewBox'])
            ->allowElement('g', ['id'])
            ->allowElement('path', ['d', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('circle', ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('rect', ['x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('line', ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'class'])
            ->allowElement('polyline', ['points', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('polygon', ['points', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('text', ['x', 'y', 'font-family', 'font-size', 'fill', 'class'])
            ->allowElement('style')
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*');

        return new HtmlSanitizer($config);
    }

    public function create(): HtmlSanitizerInterface
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('svg', ['width', 'height', 'viewBox'])
            ->allowElement('g', ['id'])
            ->allowElement('path', ['d', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('circle', ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('rect', ['x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('line', ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'class'])
            ->allowElement('polyline', ['points', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('polygon', ['points', 'fill', 'stroke', 'stroke-width', 'class'])
            ->allowElement('text', ['x', 'y', 'font-family', 'font-size', 'fill', 'class'])
            ->allowElement('style')
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*')
            ->dropAttribute('xlink:href', '*')
            ->dropAttribute('href', '*');

        return new HtmlSanitizer($config);
    }
}
