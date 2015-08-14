/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

require.config({
    paths: {
        sulusecurity: '../../sulusecurity/js'
    }
});

define(['config'], function(Config) {

    'use strict';

    return {

        name: 'Sulu Security Bundle',

        initialize: function(app) {
            var sandbox = app.sandbox;

            Config.set(
                'sulusecurity.permissions',
                [
                    {value: 'view', icon: 'eye'},
                    {value: 'add', icon: 'plus-circle'},
                    {value: 'edit', icon: 'pencil'},
                    {value: 'delete', icon: 'trash-o'},
                    {value: 'security', icon: 'unlock-alt'}
                ]
            );

            Config.set(
                'sulusecurity.permission_titles',
                [
                    'security.permissions.view',
                    'security.permissions.add',
                    'security.permissions.edit',
                    'security.permissions.delete',
                    'security.permissions.security'
                ]
            );

            Config.set('suluresource.filters.type.roles', {
                breadCrumb: [
                    {title: 'navigation.settings'},
                    {title: 'security.roles.title', link: 'settings/roles'}
                ],
                routeToList: 'settings/roles'
            });

            app.components.addSource('sulusecurity', '/bundles/sulusecurity/js/components');

            // list all roles
            sandbox.mvc.routes.push({
                route: 'settings/roles',
                callback: function() {
                    return '<div data-aura-component="roles@sulusecurity" data-aura-display="list"/>';
                }
            });

            // show form for a new role
            sandbox.mvc.routes.push({
                route: 'settings/roles/new',
                callback: function() {
                    return '<div data-aura-component="roles/components/content@sulusecurity" data-aura-display="form"/>';
                }
            });


            // show form for editing a role
            sandbox.mvc.routes.push({
                route: 'settings/roles/edit::id/:content',
                callback: function(id) {
                    return '<div data-aura-component="roles/components/content@sulusecurity" data-aura-title="sulu.roles.permissions" data-aura-display="form" data-aura-id="' + id + '"/>';
                }
            });
        }
    };
});
