/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'services/sulucontact/account-manager',
    'services/sulucontact/account-router',
    'services/sulucontact/account-delete-dialog',
], function(AccountManager, AccountRouter, DeleteDialog) {

    'use strict';

    return {

        /**
         * Returns the header config for this main-view
         * if an existing contact is edited a delete-button and a toggler get added
         * @return {Object} the header config object
         */
        header: function() {
            var config = {
                title: function() {
                    return this.data.name;
                }.bind(this),
                tabs: {
                    url: '/admin/content-navigations?alias=account',
                    options: {
                        disablerToggler: 'husky.toggler.sulu-toolbar',
                        data: function() {
                            // this.data is set by sulu-content.js with data from loadComponentData()
                            return this.sandbox.util.extend(false, {}, this.data);
                        }.bind(this)
                    }
                },
                toolbar: {
                    buttons: {
                        save: {
                            parent: 'saveWithOptions'
                        }
                    }
                }
            };
            if (!!this.options.id) {
                config.toolbar.buttons.delete = {};
                config.toolbar.buttons.disabler = {
                    parent: 'toggler',
                    options: {
                        title: 'public.locked',
                        hidden: true
                    }
                };
            }
            return config;
        },

        title: function() {
            return this.data.name;
        },

        loadComponentData: function() {
            var promise = this.sandbox.data.deferred();
            AccountManager.loadOrNew(this.options.id).then(function(data) {
                promise.resolve(data);
            });
            return promise;
        },

        initialize: function() {
            this.bindCustomEvents();
        },

        bindCustomEvents: function() {
            this.sandbox.on('sulu.header.back', AccountRouter.toList);
            this.sandbox.on('sulu.tab.dirty', this.enableSave.bind(this));
            this.sandbox.on('sulu.router.navigate', this.disableSave.bind(this));
            this.sandbox.on('sulu.toolbar.save', this.save.bind(this));
            this.sandbox.on('sulu.tab.saving', this.loadingSave.bind(this));
            this.sandbox.on('sulu.toolbar.delete', this.deleteAccount.bind(this));
        },

        deleteAccount: function() {
            DeleteDialog.showDialog([this.options.id], function(deleteContacts) {
                AccountManager.delete(this.options.id, deleteContacts).then(function() {
                    AccountRouter.toList();
                }.bind(this));
            }.bind(this));
        },

        /**
         * Sets the save-button into loading-state and tells the tab to save itselve
         * @param action {String} the after-save action
         */
        save: function(action) {
            this.saveTab().then(function(savedData) {
                this.afterSave(action, savedData);
            }.bind(this));
        },


        /**
         * Saves the tab and returns a after the tab has saved itselve
         * @returns promise with the saved data
         */
        saveTab: function() {
            var promise = $.Deferred();
            this.sandbox.once('sulu.tab.saved', function(savedData, updateData) {
                if (!!updateData){
                    this.data = savedData;
                }
                promise.resolve(savedData);
            }.bind(this));
            this.sandbox.emit('sulu.tab.save');
            return promise;
        },

        /**
         * Enables the save-button
         */
        enableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.enable', 'save', false);
        },

        /**
         * Disables the save-button
         */
        disableSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', false);
        },

        /**
         * Sets the save-button in loading-state
         */
        loadingSave: function() {
            this.sandbox.emit('sulu.header.toolbar.item.loading', 'save');
        },

        /**
         * Executes the after save action: Navigates to edit, add or list
         * @param action {String} the after-save action
         * @param savedData {Object} the data after the save-process has finished
         */
        afterSave: function(action, savedData) {
            this.sandbox.emit('sulu.header.toolbar.item.disable', 'save', true);
            if (action === 'back') {
                AccountRouter.toList();
            } else if (action === 'new') {
                AccountRouter.toAdd();
            } else if (!this.options.id) {
                AccountRouter.toEdit(savedData.id);
            }
        }
    };
});
