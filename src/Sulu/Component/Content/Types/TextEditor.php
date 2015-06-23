<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use Sulu\Component\Content\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextEditor.
 */
class TextEditor extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('TextEditor', '');

        $this->template = $template;
    }

    /**
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * returns default parameters.
     *
     * @return array
     */
    public function getDefaultParams()
    {
        return array(
            'table' => new PropertyParameter('table', true),
            'link' => new PropertyParameter('link', true),
            'height' => new PropertyParameter('height', 300),
            'max_height' => new PropertyParameter('max_height', 500),
            'paste_from_word' => new PropertyParameter('paste_from_word', true)
        );
    }
}
