<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * cleans path strings.
 */
class PathCleanup implements PathCleanupInterface
{
    /**
     * replacers for cleanup.
     *
     * @var array
     */
    protected $replacers = [];

    /**
     * @var SluggerInterface
     */
    private $slugger;

    /**
     * valid pattern for path
     * example: /products/machines
     *  + test whole input case insensitive
     *  + trailing slash
     *  + one or more sign (a-z, 0-9, -, _)
     *  + repeat.
     *
     * @var string
     */
    private $pattern = '/^(\/[a-z0-9][a-z0-9-_]*)+$/';

    public function __construct(array $replacers, ?SluggerInterface $slugger = null)
    {
        if (null === $slugger) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.1',
                'Initializing the PathCleanup without a slugger is deprecated.'
            );
            $slugger = new AsciiSlugger();
        }

        if (\method_exists($slugger, 'withEmoji')) { // BC Layer <= Symfony 6.3
            if (
                !\method_exists(\Symfony\Component\String\AbstractUnicodeString::class, 'localeUpper') // BC Layer <= Symfony 7.0
                || \class_exists(\Symfony\Component\Emoji\EmojiTransliterator::class) // Symfony >= 7.1 requires symfony/emoji
            ) {
                $slugger = $slugger->withEmoji();
            }
        }

        $this->replacers = $replacers;
        $this->slugger = $slugger;
    }

    /**
     * returns a clean string.
     *
     * @param string $dirty dirty string to cleanup
     * @param string $languageCode
     *
     * @return string clean string
     */
    public function cleanup($dirty, $languageCode = null)
    {
        $replacers = $this->replacers['default'];

        if (null !== $languageCode) {
            $replacers = \array_merge(
                $replacers,
                isset($this->replacers[$languageCode]) ? $this->replacers[$languageCode] : []
            );
            $languageCode = \str_replace('-', '_', $languageCode);
        }

        if (\count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $dirty = \str_replace($key, $value, $dirty);
            }
        }
        // replace multiple dash with one
        $dirty = \preg_replace('/([-]+)/', '-', $dirty);

        // remove dash before slash
        $dirty = \preg_replace('/[-]+\//', '/', $dirty);

        // remove dash after slash
        $dirty = \preg_replace('/\/[-]+/', '/', $dirty);

        // delete dash at the beginning or end
        $dirty = \preg_replace('/^([-])/', '', $dirty);
        $dirty = \preg_replace('/([-])$/', '', $dirty);

        // replace multiple slashes
        $dirty = \preg_replace('/([\/]+)/', '/', $dirty);

        $parts = \explode('/', $dirty);
        $newParts = [];

        $totalParts = \count($parts);
        foreach ($parts as $i => $part) {
            $slug = $this->slugger->slug($part, '-', $languageCode);
            $slug = $slug->lower();
            if (0 === $i || $i + 1 === $totalParts || !$slug->isEmpty()) {
                $newParts[] = $slug->toString();
            }
        }

        return \implode('/', $newParts);
    }

    /**
     * returns TRUE if path is valid.
     *
     * @param string $path
     *
     * @return bool
     */
    public function validate($path)
    {
        return '/' === $path || 1 === \preg_match($this->pattern, $path);
    }
}
