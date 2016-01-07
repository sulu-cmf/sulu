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
        suluwebsite: '../../suluwebsite/js',
        suluwebsitecss: '../../suluwebsite/css'
    }
});

define(['css!suluwebsitecss/main'], function() {
    return {

        name: "SuluWebsiteBundle",

        initialize: function(app) {

            'use strict';

            var sandbox = app.sandbox;

            app.sandbox.website = {
                /**
                 * Clear the cache for the website.
                 */
                cacheClear: function() {
                    app.sandbox.util.load('/admin/website/cache/clear')
                        .then(function() {
                            app.sandbox.emit('sulu.labels.success.show', 'sulu.website.cache.remove.success.description', 'sulu.website.cache.remove.success.title', 'cache-success');
                        }.bind(this))
                        .fail(function() {
                            app.sandbox.emit('sulu.labels.error.show', 'sulu.website.cache.remove.error.description', 'sulu.website.cache.remove.error.title', 'cache-error');
                        }.bind(this));
                }
            };

            app.components.addSource('suluwebsite', '/bundles/suluwebsite/js/components');

            // cache clear button
            sandbox.mvc.routes.push({
                route: 'settings/cache',
                callback: function() {
                    return '<div data-aura-component="cache@suluwebsite"/>';
                }
            });
        }
    };
});
