<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

use Exception;

class RestException extends Exception
{
    public function toArray()
    {
        return array(
            'code' => $this->code,
            'message' => $this->message,
        );
    }
}
