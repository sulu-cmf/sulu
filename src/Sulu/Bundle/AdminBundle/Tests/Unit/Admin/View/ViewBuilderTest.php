<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilder;

class ViewBuilderTest extends TestCase
{
    public static function provideBuildView()
    {
        return [
            [
                'sulu_tag.add_form.details',
                '/tags/add',
                'sulu_admin.form',
                ['test1' => 'value1'],
                ['attribute1' => 'defaultValue1'],
                'sulu_tag.add_form',
                ['tag'],
            ],
            [
                'sulu_tag.list',
                '/tags',
                'sulu_admin.list',
                ['test2' => 'value2', 'test3' => 'value3'],
                ['attribute2' => 'defaultValue2', 'attribute3' => 'defaultValue3'],
                null,
                ['webspace'],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBuildView')]
    public function testBuildTabView(
        string $name,
        string $path,
        string $type,
        array $options,
        array $attributeDefaults,
        ?string $parent,
        array $rerenderAttributes
    ): void {
        $viewBuilder = new ViewBuilder($name, $path, $type);
        $expectedView = new View($name, $path, $type);

        foreach ($options as $optionKey => $optionValue) {
            $viewBuilder->setOption($optionKey, $optionValue);
            $expectedView->setOption($optionKey, $optionValue);
        }

        foreach ($attributeDefaults as $attributeDefaultKey => $attributeDefaultValue) {
            $viewBuilder->setAttributeDefault($attributeDefaultKey, $attributeDefaultValue);
            $expectedView->setAttributeDefault($attributeDefaultKey, $attributeDefaultValue);
        }

        if ($parent) {
            $viewBuilder->setParent($parent);
            $expectedView->setParent($parent);
        }

        foreach ($rerenderAttributes as $rerenderAttribute) {
            $viewBuilder->addRerenderAttribute($rerenderAttribute);
            $expectedView->addRerenderAttribute($rerenderAttribute);
        }

        $this->assertEquals($expectedView, $viewBuilder->getView());
    }

    public function testOverrideType(): void
    {
        $viewBuilder = new ViewBuilder('sulu_admin.test', '/test', 'test1');
        $viewBuilder->setType('test2');

        $expectedView = new View('sulu_admin.test', '/test', 'test2');

        $this->assertEquals($expectedView, $viewBuilder->getView());
    }

    public function testGetName(): void
    {
        $viewBuilder = new ViewBuilder('sulu_admin.test', '/test', 'test1');

        $this->assertEquals('sulu_admin.test', $viewBuilder->getName());
    }
}
