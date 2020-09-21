<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Admin\View;

use Sulu\Bundle\AdminBundle\Admin\View\SaveWithFormDialogToolbarAction;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SaveWithFormDialogToolbarActionTest extends SuluTestCase
{
    public function testSerializer()
    {
        $saveWithFormDialogToolbarAction = new SaveWithFormDialogToolbarAction('title', 'form');

        $this->assertEquals(
            '{"type":"sulu_admin.save_with_form_dialog","options":{"formKey":"form","title":"title"}}',
            $this->getContainer()->get('jms_serializer')->serialize($saveWithFormDialogToolbarAction, 'json')
        );
    }
}
