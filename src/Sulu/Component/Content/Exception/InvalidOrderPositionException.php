<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Component\Content\Exception;

/**
 * Thrown when the xml definition of a template contains an error
 * @package Sulu\Component\Content\Mapper\Exception
 */
class InvalidOrderPositionException extends \Exception
{
    public function __construct($message = 'The position at which a node should get arranged must exist')
    {
        parent::__construct($message);
    }
}
