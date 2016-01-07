/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['underscore', 'text!./skeleton.html'], function(_, skeleton) {

    'use strict';

    var defaults = {
        templates: {
            skeleton: skeleton,
            url: '/admin/api/webspaces/<%= webspace.key %>/analytics<% if (!!id) { %>/<%= id %><% } %><% if (!!ids) { %>?ids=<%= ids.join(",") %><% } %>'
        },
        translations: {
            title: 'public.title',
            type: 'public.type',
            domains: 'website.webspace.settings.domains',
            allDomains: 'website.webspace.settings.all-domains',
            successLabel: 'labels.success',
            successMessage: 'labels.success.save-desc'
        }
    };

    return {

        defaults: defaults,

        tabOptions: {
            title: function() {
                return this.data.title;
            }
        },

        layout: {
            content: {
                width: 'max',
                leftSpace: true,
                rightSpace: true
            }
        },

        initialize: function() {
            this.render();

            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.toolbar.add', this.editAnalytics.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteAnalytics.bind(this));
        },

        render: function() {
            this.html(this.templates.skeleton());

            this.sandbox.start([
                {
                    name: 'list-toolbar@suluadmin',
                    options: {
                        el: '#webspace-analytics-list-toolbar',
                        instanceName: 'analytics',
                        hasSearch: false,
                        template: this.sandbox.sulu.buttons.get({
                            add: {},
                            deleteSelected: {}
                        })
                    }
                },
                {
                    name: 'datagrid@husky',
                    options: {
                        el: '#webspace-analytics-list',
                        url: this.templates.url({webspace: this.data, id: null, ids: null}),
                        resultKey: 'analytics',
                        actionCallback: this.editAnalytics.bind(this),
                        pagination: 'infinite-scroll',
                        viewOptions: {
                            table: {
                                actionIconColumn: 'title'
                            }
                        },
                        matchings: [
                            {
                                attribute: 'title',
                                content: this.translations.title
                            },
                            {
                                attribute: 'type',
                                content: this.translations.type
                            },
                            {
                                attribute: 'domains',
                                content: this.translations.domains,
                                type: function(content) {
                                    var urls = _.map(content, function(item) {
                                        return item['url'];
                                    });

                                    return urls.join(', ');
                                }
                            },
                            {
                                attribute: 'allDomains',
                                content: this.translations.allDomains,
                                type: 'checkbox_readonly'
                            }
                        ]
                    }
                }
            ]);
        },

        editAnalytics: function(id) {
            this.sandbox.start([
                {
                    name: 'webspace/settings/analytics/overlay@suluwebsite',
                    options: {
                        el: '#webspace-analytics-form-overlay',
                        id: id,
                        webspaceKey: this.data.key,
                        urls: this.data.urls,
                        saveCallback: this.save.bind(this),
                        translations: this.translations
                    }
                }
            ])
        },

        save: function(id, data) {
            this.sandbox.util.save(
                this.templates.url({webspace: this.data, id: id, ids: null}), !!id ? 'PUT' : 'POST', data
            ).then(function(response) {
                var event = 'husky.datagrid.record.add';
                if (!!id) {
                    event = 'husky.datagrid.records.change';
                }

                this.sandbox.emit(event, response);
                this.sandbox.emit('sulu.labels.success.show', this.translations.successMessage, this.translations.successLabel);
            }.bind(this));
        },

        deleteAnalytics: function() {
            var ids = JSON.parse(JSON.stringify($('#webspace-analytics-list').data('selected')));

            this.sandbox.sulu.showDeleteDialog(function(confirmed) {
                if (!!confirmed) {
                    this.sandbox.util.save(
                        this.templates.url({webspace: this.data, id: null, ids: ids}), 'DELETE'
                    ).then(function() {
                        for (var i = 0, length = ids.length; i < length; i++) {
                            var id = ids[i];
                            this.sandbox.emit('husky.datagrid.record.remove', id);
                        }
                    }.bind(this));
                }
            }.bind(this));
        },

        loadComponentData: function() {
            var deferred = this.sandbox.data.deferred();

            deferred.resolve(this.options.data());

            return deferred.promise();
        }
    };
});
