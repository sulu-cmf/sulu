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
        sulucategory: '../../sulucategory/js',
        "type/categoryList": '../../sulucategory/js/validation/types/categoryList'
    }
});

define({

    name: "SuluCategoryBundle",

    initialize: function(app) {

        'use strict';

        var sandbox = app.sandbox;

        app.components.addSource('sulucategory', '/bundles/sulucategory/js/components');

        sandbox.mvc.routes.push({
            route: 'settings/categories',
            callback: function() {
                return '<div data-aura-component="categories@sulucategory" data-aura-display="list"/>';
            }
        });

        sandbox.mvc.routes.push({
            route: 'settings/categories/new/:parent/:content',
            callback: function(parent) {
                return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-parent="'+ parent +'"/>';
            }
        });

        sandbox.mvc.routes.push({
            route: 'settings/categories/new/:content',
            callback: function() {
                return '<div data-aura-component="categories@sulucategory" data-aura-display="edit"/>';
            }
        });

        sandbox.mvc.routes.push({
            route: 'settings/categories/edit::id/:content',
            callback: function(id) {
                return '<div data-aura-component="categories@sulucategory" data-aura-display="edit" data-aura-id="'+ id +'"/>';
            }
        });
    }
});
