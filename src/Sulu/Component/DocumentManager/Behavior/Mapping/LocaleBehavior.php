<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Behavior\Mapping;

/**
 * Populate the locale.
 */
interface LocaleBehavior
{
    /**
     * Return the documents locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Sets the document locale.
     *
     * @param string $locale
     *
     * @return void
     */
    public function setLocale($locale);

    /**
     * Return the documents original locale.
     *
     * @return string
     */
    public function getOriginalLocale();

    /**
     * Sets the document original locale.
     *
     * @param string $locale
     *
     * @return void
     */
    public function setOriginalLocale($locale);
}
