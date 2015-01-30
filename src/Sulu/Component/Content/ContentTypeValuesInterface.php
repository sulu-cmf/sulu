<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Interface ContentTypeValuesInterface
 * @package Sulu\Component\Content
 */
interface ContentTypeValuesInterface
{
    const TYPE_SERVICE = 'service';
    const TYPE_STATIC = 'static';

    /**
     * returns the type of the PropertyValues
     * @return string
     */
    public function getType();

    /**
     * @param ContainerAware $container
     * @return PropertyValueInterface[]
     */
    public function loadValues(ContainerAware $container = null);
} 
