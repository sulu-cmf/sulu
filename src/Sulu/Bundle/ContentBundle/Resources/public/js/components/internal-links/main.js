/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles media selection
 *
 * @class MediaSelection
 * @constructor
 */
define([], function() {

    'use strict';

    var defaults = {
            eventNamespace: 'sulu.internal-links',
            resultKey: 'nodes',
            idKey: 'uuid',
            columnNavigationUrl: '',
            hideConfigButton: true,
            hidePositionElement: true,
            dataAttribute: 'internal-links',
            dataDefault: [],
            translations: {
                noContentSelected: 'internal-links.nolinks-selected',
                addLinks: 'internal-links.add',
                visible: 'public.visible',
                of: 'public.of'
            }
        },

        templates = {
            data: function(options) {
                return [
                    '<div id="', options.ids.columnNavigation, '"/>',
                ].join('');
            },

            contentItem: function(value) {
                return ['<span class="value">', value, '</span>'].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        /**
         * custom event handling
         */
        bindCustomEvents = function() {
            this.sandbox.on('husky.overlay.internal-links.' + this.options.instanceName + '.add.initialized', initColumnNavigation.bind(this));

            this.sandbox.on('husky.column-navigation.' + this.options.instanceName + '.action', selectLink.bind(this));

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.column-navigation.' + this.options.instanceName + '.initialized', function() {
                this.sandbox.emit('husky.overlay.internal-links.' + this.options.instanceName + '.add.set-position');
            }.bind(this));
        },

        /**
         * Handles the selection of a link
         * @param item {Object} the object of the link node
         */
        selectLink = function(item) {
            var data = this.getData();

            if (data.indexOf(item.id) === -1) {
                // FIXME return of node api returns for column-navigation id and for "filter by id" uuid as id key
                item.uuid = item.id;
                
                data.push(item.id);

                this.setData(data, false);

                if (!!item.publishedState) {
                    this.addItem(item);
                }
            }
        },

        /**
         * initialize column navigation
         */
        initColumnNavigation = function() {
            var data = this.getData();

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: getId.call(this, 'columnNavigation'),
                            url: this.options.columnNavigationUrl,
                            instanceName: this.options.instanceName,
                            actionIcon: 'fa-plus-circle',
                            resultKey: this.options.resultKey,
                            showOptions: false,
                            showStatus: true,
                            responsive: false,
                            skin: 'fixed-height-small',
                            markable: true,
                            sortable: false,
                            premarkedIds: data
                        }
                    }
                ]
            );
        },

        /**
         * starts the overlay component
         */
        startAddOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$addButton,
                        cssClass: 'internal-links-overlay',
                        el: $element,
                        container: this.$el,
                        removeOnClose: false,
                        instanceName: 'internal-links.' + this.options.instanceName + '.add',
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.addLinks),
                                cssClass: 'internal-links-overlay-add',
                                data: templates.data(this.options)
                            }
                        ]
                    }
                }
            ]);
        };

    return {
        type: 'itembox',

        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);

            // init ids
            this.options.ids = {
                container: 'internal-links-' + this.options.instanceName + '-container',
                addButton: 'internal-links-' + this.options.instanceName + '-add',
                configButton: 'internal-links-' + this.options.instanceName + '-config',
                displayOption: 'internal-links-' + this.options.instanceName + '-display-option',
                content: 'internal-links-' + this.options.instanceName + '-content',
                chooseTab: 'internal-links-' + this.options.instanceName + '-choose-tab',
                columnNavigation: 'internal-links-' + this.options.instanceName + '-column-navigation'
            };

            this.render();

            // sandbox event handling
            bindCustomEvents.call(this);

            // init overlays
            startAddOverlay.call(this);
        },

        getUrl: function(data) {
            var delimiter = (this.options.url.indexOf('?') === -1) ? '?' : '&';

            return [this.options.url, delimiter, this.options.idsParameter, '=', (data || []).join(',')].join('');
        },

        getItemContent: function(item) {
            return templates.contentItem(item.title);
        },

        sortHandler: function(ids) {
            this.setData(ids, false);
        },

        removeHandler: function(id) {
            var data = this.getData();

            for (var i = -1, length = data.length; ++i < length;) {
                if (id === data[i]) {
                    data.splice(i, 1);
                    break;
                }
            }

            this.setData(data, false);
        }
    };
});
