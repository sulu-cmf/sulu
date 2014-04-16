/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(function() {

    'use strict';

    var SHOW_GHOST_PAGES_KEY = 'column-navigation-show-ghost-pages',

    templates = {
        toggler: [
            '<div id="show-ghost-pages"></div>',
            '<label class="inline spacing-left" for="show-ghost-pages"><%= label %></label>'
        ].join('')
    };

    return {

        view: true,

        header: function() {
            return {
                title: this.options.webspace.replace(/_/g, '.'),
                breadcrumb: [
                    {title: this.options.webspace.replace(/_/g, '.')}
                ]
            };
        },

        initialize: function() {
            this.render();

            this.showGhostPages = true;
            this.setShowGhostPages();
        },

        /**
         * Sets the show-ghost-pages configuration to stored user settings if there is one
         */
        setShowGhostPages: function() {
            var showGhostPages = this.sandbox.sulu.getUserSetting(SHOW_GHOST_PAGES_KEY);
            if (showGhostPages !== null) {
                this.showGhostPages = JSON.parse(showGhostPages);
            }
        },

        bindCustomEvents: function() {
            this.sandbox.on('husky.column-navigation.add', function(parent) {
                this.sandbox.emit('sulu.content.contents.new', parent);
            }, this);

            this.sandbox.on('husky.column-navigation.edit', function(item) {
                this.sandbox.emit('sulu.content.contents.load', item.id);
            }, this);

            this.sandbox.on('sulu.content.localizations', function(localizations) {
                this.localizations = localizations;
            }, this);

            this.sandbox.on('husky.toggler.show-ghost-pages.changed', function(checked) {
                this.showGhostPages = checked;
                this.sandbox.sulu.saveUserSetting(SHOW_GHOST_PAGES_KEY, this.showGhostPages);
                this.startColumnNavigation();
            }, this);

            this.sandbox.on('husky.select.language.selected.item', function(localeId) {
                this.changeLanguage(this.getLocalizationForId(localeId));
            }, this);

            // change language
            this.sandbox.on('sulu.dropdown.languages.item-clicked', function(item) {
                this.changeLanguage(item.localization);
            }, this);
        },

        startColumnNavigation: function() {
            this.sandbox.stop(this.$find('#content-column'));
            this.sandbox.dom.append(this.$el, '<div id="content-column"></div>');

            this.sandbox.start([
                {
                    name: 'column-navigation@husky',
                    options: {
                        el: this.$find('#content-column'),
                        url: this.getUrl()
                    }
                }
            ]);
        },

        getLocalizationForId: function(id) {
            id = parseInt(id, 10);
            for (var i = -1, length = this.localizations.length; ++i < length;) {
                if (this.localizations[i].id === id) {
                    return this.localizations[i].localization;
                }
            }
            return null;
        },

        getUrl: function() {
            return '/admin/api/nodes?depth=1&webspace=' + this.options.webspace + '&language=' + this.options.language + '&exclude-ghosts=' + (!this.showGhostPages ? 'true' : 'false');
        },

        changeLanguage: function(language) {
            this.sandbox.emit('sulu.content.contents.list', this.options.webspace, language);
        },

        render: function() {
            this.bindCustomEvents();

            require(['text!/admin/content/template/content/column/' + this.options.webspace + '/' + this.options.language + '.html'], function(template) {
                var defaults = {
                        translate: this.sandbox.translate
                    },
                    context = this.sandbox.util.extend({}, defaults),
                    tpl = this.sandbox.util.template(template, context);

                this.sandbox.dom.html(this.$el, tpl);

                this.addLocaleDropdown();
                this.addToggler();

                // start column-navigation
                this.startColumnNavigation();

            }.bind(this));
        },

        /**
         * Generates the toggler and adds it to the header
         */
        addToggler: function() {
            this.sandbox.emit('sulu.header.set-bottom-content', this.sandbox.util.template(templates.toggler)({
                label: this.sandbox.translate('content.contents.show-ghost-pages')
            }));

            this.sandbox.start([{
                name: 'toggler@husky',
                options: {
                    el: '#show-ghost-pages',
                    checked: this.showGhostPages,
                    outline: true
                }
            }]);
        },

        /**
         * Generates the locale-dropdown and adds it to the header
         * //TODO: abstract in adminbundle
         */
        addLocaleDropdown: function() {
            this.sandbox.on('husky.toolbar.header.items.set', function() {
                this.sandbox.emit('sulu.header.toolbar.item.change', 'language', this.options.language);
            }.bind(this));

            var options = {
                groups: [{
                    id: 'right',
                    align: 'right'
                }],
                data: [{
                    id: 'language',
                    group: 'right',
                    position: 10,
                    type: 'select',
                    title: '',
                    class: 'highlight-white',
                    itemsOption: {
                        url: '/admin/content/languages/' + this.options.webspace,
                        titleAttribute: 'name',
                        idAttribute: 'localization',
                        translate: false,
                        callback: function(item) {
                            this.sandbox.emit('sulu.dropdown.languages.item-clicked', item);
                        }.bind(this)
                    }
                }]
            };

            this.sandbox.emit('sulu.header.set-toolbar', options);
        }
    };
});
