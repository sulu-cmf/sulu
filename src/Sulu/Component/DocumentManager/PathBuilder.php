<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

/**
 * The path builder provides a way to create paths from templates.
 */
class PathBuilder
{
    public function __construct(private PathSegmentRegistry $registry)
    {
    }

    /**
     * Build a path from an array of path segments.
     *
     * Segments demarcated by "%" characters will be interpreted as path
     * segment *names* and their value will be resolved from the PathSegmentRegistry.
     *
     * Other segments will be interpreted literally.
     *
     * The following:
     *
     * ````
     * $path = $pathBuilder->build(array('%base%', 'hello', '%articles%'));
     * ````
     *
     * Will yield `/cms/hello/articleDirectory` where `%base%` is "cms" and
     * `%articles` is "articleDirectory"
     *
     * @see Sulu\Component\DocumentManager\PathSegmentRegistry
     *
     * @return string
     */
    public function build(array $segments)
    {
        $results = [];
        foreach ($segments as $segment) {
            $result = $this->buildSegment($segment);

            if (null === $result) {
                continue;
            }

            $results[] = $result;
        }

        return '/' . \implode('/', $results);
    }

    /**
     * @param string $segment
     */
    private function buildSegment($segment)
    {
        if (empty($segment) || '/' == $segment) {
            return;
        }

        if ('%' == \substr($segment, 0, 1)) {
            if ('%' == \substr($segment, -1)) {
                return $this->registry->getPathSegment(\substr($segment, 1, -1));
            }
        }

        return $segment;
    }
}
