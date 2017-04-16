<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\TagBundle\Controller\Exception;

use Sulu\Component\Rest\Exception\RestException;

/**
 * TODO: move to sulu lib https://github.com/sulu-cmf/SuluTagBundle/issues/11
 * This exception should be thrown when a constraint violation for a enitity occures
 * @package Sulu\Bundle\TagBundle\Controller\Exception
 */
class ConstraintViolationException extends RestException {


    /**
     * The field of the tag which is not unique
     * @var string
     */
    protected $field;

    /**
     * @param string $message The error message
     * @param string $field The field which is not
     */
    public function __construct($message, $field)
    {
        $this->field = $field;
        parent::__construct($message, 0);
    }


    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
            'field' => $this->field
        );
    }

}
