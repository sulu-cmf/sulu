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

use HtmlSanitizer\Sanitizer;
use HtmlSanitizer\SanitizerInterface;

/**
 * @internal
 */
final class SvgSanitizerFactory
{
    public function createSafe(): SanitizerInterface
    {
        return Sanitizer::create([
            'tags' => [
                'svg' => [
                    'allowed_attributes' => ['width', 'height', 'viewBox', 'class', 'style'],
                ],
                'g' => [
                    'allowed_attributes' => ['id', 'class', 'style'],
                ],
                'path' => [
                    'allowed_attributes' => ['d', 'fill', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'circle' => [
                    'allowed_attributes' => ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'rect' => [
                    'allowed_attributes' => ['x', 'y', 'width', 'height', 'fill', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'line' => [
                    'allowed_attributes' => ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'polyline' => [
                    'allowed_attributes' => ['points', 'fill', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'polygon' => [
                    'allowed_attributes' => ['points', 'fill', 'stroke', 'stroke-width', 'class', 'style'],
                ],
                'text' => [
                    'allowed_attributes' => ['x', 'y', 'font-family', 'font-size', 'fill', 'class', 'style'],
                ],
                'style' => [
                    'allowed_attributes' => [],
                ],
                'abbr' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'a' => [
                    'allowed_attributes' => ['href', 'title', 'class', 'style'],
                    'allowed_hosts' => null,
                    'allow_mailto' => true,
                    'force_https' => false,
                ],
                'blockquote' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'br' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'caption' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'code' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'dd' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'del' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'details' => [
                    'allowed_attributes' => ['open', 'class', 'style'],
                ],
                'div' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'dl' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'dt' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'em' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'figcaption' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'figure' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h1' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h2' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h3' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h4' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h5' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'h6' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'hr' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'i' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'li' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'ol' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'pre' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'p' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'q' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'rp' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'rt' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'ruby' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'small' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'span' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'strong' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'sub' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'summary' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'sup' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'table' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'tbody' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'td' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'tfoot' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'thead' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'th' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'tr' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'u' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
                'ul' => [
                    'allowed_attributes' => ['class', 'style'],
                ],
            ],
        ]);
    }

    public function create(): SanitizerInterface
    {
        return Sanitizer::create([]);
    }
}
