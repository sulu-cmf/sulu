<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Loader\Exception;

use Sulu\Component\Content\Structure\Loader\Exception\InvalidXmlException;

/**
 * Thrown when a template does not contain a required property name
 * @package Sulu\Component\Content\Template\Exception
 */
class RequiredPropertyNameNotFoundException extends InvalidXmlException
{
    /**
     * The name of the property, which is required, but not found
     * @var string
     */
    protected $propertyName;

    public function __construct($template, $propertyName)
    {
        $this->propertyName = $propertyName;
        parent::__construct(
            $template,
            sprintf(
                'The property with the name "%s" is required, but was not found in the template "%s"',
                $this->propertyName,
                $template
            )
        );
    }
}
