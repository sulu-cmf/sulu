<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Routing\FormRouteBuilder;

class FormRouteBuilderTest extends TestCase
{
    public function testBuildFormRouteWithClone()
    {
        $routeBuilder = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles');

        $this->assertNotSame($routeBuilder->getRoute(), $routeBuilder->getRoute());
    }

    public function testBuildFormRouteWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $route = (new FormRouteBuilder('sulu_category.edit_form.detail', '/details'))
            ->getRoute();
    }

    public function provideBuildFormRoute()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'sulu_category.edit_form',
                'sulu_category.datagrid',
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                'tags',
                null,
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideBuildFormRoute
     */
    public function testBuildFormRoute(
        string $name,
        string $path,
        string $resourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $editRoute,
        ?string $backRoute
    ) {
        $routeBuilder = (new FormRouteBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey);

        if ($tabTitle) {
            $routeBuilder->setTabTitle($tabTitle);
        }

        if ($editRoute) {
            $routeBuilder->setEditRoute($editRoute);
        }

        if ($backRoute) {
            $routeBuilder->setBackRoute($backRoute);
        }

        $route = $routeBuilder->getRoute();

        $this->assertEquals($name, $route->getName());
        $this->assertEquals($path, $route->getPath());
        $this->assertEquals($resourceKey, $route->getOption('resourceKey'));
        $this->assertEquals($formKey, $route->getOption('formKey'));
        $this->assertEquals($tabTitle, $route->getOption('tabTitle'));
        $this->assertEquals($editRoute, $route->getOption('editRoute'));
        $this->assertEquals($backRoute, $route->getOption('backRoute'));
        $this->assertNull($route->getParent());
        $this->assertEquals('sulu_admin.form', $route->getView());
    }

    public function testBuildFormWithToolbarActions()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addToolbarActions(['sulu_admin.save', 'sulu_admin.types'])
            ->addToolbarActions(['sulu_admin.delete'])
            ->getRoute();

        $this->assertEquals(
            ['sulu_admin.save', 'sulu_admin.types', 'sulu_admin.delete'],
            $route->getOption('toolbarActions')
        );
    }

    public function testBuildFormWithRouterAttributesToFormStore()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToFormStore(['webspace', 'parent'])
            ->addRouterAttributesToFormStore(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace', 'parent', 'locale'],
            $route->getOption('routerAttributesToFormStore')
        );
    }

    public function testBuildFormWithRouterAttributesToEditRoute()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToEditRoute(['webspace', 'parent'])
            ->addRouterAttributesToEditRoute(['locale'])
            ->getRoute();

        $this->assertEquals(
            ['webspace', 'parent', 'locale'],
            $route->getOption('routerAttributesToEditRoute')
        );
    }

    public function testBuildFormWithIdQueryParameter()
    {
        $route = (new FormRouteBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getRoute();

        $this->assertEquals(
            'contactId',
            $route->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithPreviewCondition()
    {
        $route = (new FormRouteBuilder('sulu_content.page_edit_form.detail', '/pages/:id/details'))
            ->setResourceKey('pages')
            ->setFormKey('pages')
            ->setPreviewCondition('nodeType == 1')
            ->getRoute();

        $this->assertEquals('nodeType == 1', $route->getOption('preview'));
    }

    public function testBuildFormWithLocales()
    {
        $route = (new FormRouteBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();

        $this->assertEquals(['de', 'en', 'nl', 'fr'], $route->getOption('locales'));
    }

    public function testBuildFormWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new FormRouteBuilder('sulu_role.datagrid', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getRoute();
    }

    public function testBuildFormWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $route = (new FormRouteBuilder('sulu_role.datagrid', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->getRoute();
    }
}
