/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'jquery',
    'services/sulucategory/category-manager',
    'services/sulucategory/category-router'
], function($, categoryManager, categoryRouter) {

    'use strict';

    var constants = {
        toolbarSelector: '#list-toolbar-container',
        listSelector: '#categories-list',
        lastClickedCategorySettingsKey: 'categoriesLastClicked'
    };

    return {

        stickyToolbar: true,

        layout: {
            content: {
                width: 'max'
            }
        },

        header: function() {
            return {
                noBack: true,

                title: 'category.categories.title',
                underline: false,

                toolbar: {
                    buttons: {
                        add: {},
                        deleteSelected: {
                            options: {
                                callback: function() {
                                    this.sandbox.emit(
                                        'husky.datagrid.items.get-selected',
                                        this.deleteSelected.bind(this)
                                    );
                                }.bind(this)
                            }
                        },
                        moveSelected: {
                            options: {
                                icon: 'arrows',
                                title: 'sulu.category.move',
                                disabled: true,
                                callback: function() {
                                    this.sandbox.emit(
                                        'husky.datagrid.items.get-selected',
                                        this.moveSelected.bind(this)
                                    );
                                }.bind(this)
                            }
                        },
                        export: {
                            options: {
                                urlParameter: {
                                    flat: true,
                                    locale: this.options.locale
                                },
                                url: '/admin/api/categories.csv'
                            }
                        }
                    },
                    languageChanger: {
                        preSelected: this.options.locale
                    }
                }
            };
        },

        templates: ['/admin/category/template/category/list'],

        initialize: function() {
            this.locale = this.options.locale;

            this.sandbox.sulu.triggerDeleteSuccessLabel('labels.success.category-delete-desc');
            this.bindCustomEvents();
            this.render();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.language-changed', this.changeLanguage.bind(this));
            this.sandbox.on('husky.datagrid.item.click', this.saveLastClickedCategory.bind(this));
            this.sandbox.on('sulu.toolbar.add', this.addNewCategory.bind(this));

            // checkbox clicked
            this.sandbox.on('husky.datagrid.number.selections', function(number) {
                var postfix = number > 0 ? 'enable' : 'disable';
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'deleteSelected', false);
                this.sandbox.emit('sulu.header.toolbar.item.' + postfix, 'moveSelected', false);
            }.bind(this));
        },

        /**
         * Triggered when locale was changed.
         *
         * @param {{id}} localeItem
         */
        changeLanguage: function(localeItem) {
            this.locale = localeItem.id;
        },

        /**
         * Renderes the component
         */
        render: function() {
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/category/template/category/list'));

            // init list-toolbar and datagrid
            this.sandbox.sulu.initListToolbarAndList.call(this,
                'categories',
                '/admin/api/categories/fields?locale=' + this.locale,
                {
                    el: this.$find(constants.toolbarSelector),
                    template: 'default',
                    instanceName: this.instanceName
                },
                {
                    el: this.$find(constants.listSelector),
                    url: '/admin/api/categories?flat=true&sortBy=name&sortOrder=asc&locale=' + this.locale,
                    childrenPropertyName: 'hasChildren',
                    expandIds: [this.sandbox.sulu.getUserSetting(constants.lastClickedCategorySettingsKey)],
                    resultKey: 'categories',
                    storageName: 'categories',
                    searchFields: ['name'],
                    pagination: false,
                    actionCallback: this.editCategory.bind(this),
                    viewOptions: {
                        table: {
                            hideChildrenAtBeginning: false,
                            cropContents: false,
                            selectItem: {
                                type: 'checkbox',
                                inFirstCell: true
                            },
                            icons: [
                                {
                                    column: 'name',
                                    icon: 'plus-circle',
                                    callback: this.addNewCategory.bind(this)
                                }
                            ],
                            badges: [
                                {
                                    column: 'name',
                                    callback: function(item, badge) {
                                        if (item.defaultLocale === item.locale && item.locale !== this.locale) {
                                            badge.title = item.locale;

                                            return badge;
                                        }
                                    }.bind(this)
                                }
                            ],
                            cssClasses: [
                                {
                                    column: 'name',
                                    callback: function(item) {
                                        if (item.defaultLocale === item.locale && item.locale !== this.locale) {
                                            return 'row-gray';
                                        }
                                    }.bind(this)
                                }
                            ]
                        }
                    }
                }
            );
        },

        /**
         * Navigates to the the form for adding a new category
         * @param parent
         */
        addNewCategory: function(parent) {
            this.saveLastClickedCategory(parent);
            this.sandbox.emit('sulu.category.categories.form-add', parent);
        },

        /**
         * Navigates to the form for editing an existing category
         * @param id
         */
        editCategory: function(id) {
            this.saveLastClickedCategory(id);
            this.sandbox.emit('sulu.category.categories.form', id);
        },

        /**
         * Saves an id as the last click category in the user-settings
         * @param id {Number|String} the id of the category
         */
        saveLastClickedCategory: function(id) {
            if (!!id) {
                this.sandbox.sulu.saveUserSetting(constants.lastClickedCategorySettingsKey, id);
            }
        },

        /**
         * Deletes all selected categories
         */
        deleteSelected: function(categories) {
            this.sandbox.emit('sulu.category.categories.delete', categories, function(deletedId) {
                this.sandbox.emit('husky.datagrid.record.remove', deletedId);
            }.bind(this), function() {
                this.sandbox.emit('sulu.labels.success.show', 'labels.success.category-delete-desc', 'labels.success');
            }.bind(this));
        },

        /**
         * Moves all selected categories
         */
        moveSelected: function(categories) {
            var $componentContainer = $('<div/>');
            this.$el.append($componentContainer);

            this.sandbox.start([{
                name: 'categories/list/move-overlay@sulucategory',
                options: {
                    el: $componentContainer,
                    locale: this.options.locale,
                    selectCallback: function(parent) {
                        var defs = [];
                        for (var i = 0; i < categories.length; i++) {
                            defs.push(categoryManager.move(categories[i], this.options.locale, parent));
                        }

                        $.when.apply($, defs).done(function() {
                            this.saveLastClickedCategory(parent);

                            categoryRouter.toList(this.options.locale);
                        }.bind(this));
                    }.bind(this)
                }
            }]);
        }
    };
});
