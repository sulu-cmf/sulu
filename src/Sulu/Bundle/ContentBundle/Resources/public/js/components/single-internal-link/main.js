/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * handles single internal link selection
 *
 * @class SingleInternalLink
 * @constructor
 */
define([], function() {

    'use strict';

    var defaults = {
            visibleItems: 999,
            instanceName: null,
            url: '',
            columnNavigationUrl: '',
            idsParameter: 'ids',
            preselected: null,
            idKey: 'id',
            titleKey: 'title',
            resultKey: '',
            translations: {
                overlayTitle: 'single-internal-link.title',
                noTitle: 'public.no-title'
            }
        },

        /**
         * namespace for events
         * @type {string}
         */
        eventNamespace = 'sulu.single-internal-link.',

        /**
         * raised when the overlay data has been changed
         * @event sulu.internal-links.data-changed
         */
        DATA_CHANGED = function() {
            return createEventName.call(this, 'data-changed');
        },

        /**
         * returns normalized event names
         */
        createEventName = function(postFix) {
            return eventNamespace + (this.options.instanceName ? this.options.instanceName + '.' : '') + postFix;
        },

        templates = {
            skeleton: function(options) {
                return [
                    '<div class="grid-row" id="', options.ids.container, '">',
                    '   <div class="grid-col-11"><input type="text" class="form-element preview-update trigger-save-button" readonly="readonly" id="', options.ids.input, '"/></div>',
                    '   <div class="grid-col-1"><div class="btn action only-icon" id="', options.ids.button, '"><span class="fa-search icon"></span></div></div>',
                    '</div>'
                ].join('');
            },

            data: function(options) {
                return[
                    '<div id="', options.ids.columnNavigation, '"/>',
                ].join('');
            }
        },

        /**
         * returns id for given type
         */
        getId = function(type) {
            return '#' + this.options.ids[type];
        },

        /**
         * render component
         */
        render = function() {
            // init ids
            this.options.ids = {
                container: 'single-internal-link-' + this.options.instanceName + '-container',
                input: 'single-internal-link-' + this.options.instanceName + '-input',
                button: 'single-internal-link-' + this.options.instanceName + '-button',
                columnNavigation: 'single-internal-link-' + this.options.instanceName + '-column-navigation'
            };
            this.sandbox.dom.html(this.$el, templates.skeleton(this.options));

            // init container
            this.$container = this.sandbox.dom.find(getId.call(this, 'container'), this.$el);
            this.$input = this.sandbox.dom.find(getId.call(this, 'input'), this.$el);
            this.$button = this.sandbox.dom.find(getId.call(this, 'button'), this.$el);

            // set preselected values
            if (!!this.sandbox.dom.data(this.$el, 'single-internal-link')) {
                var data = this.sandbox.dom.data(this.$el, 'single-internal-link');
                setData.call(this, data);
            } else {
                setData.call(this, this.options.preselected);
            }

            // sandbox event handling
            bindCustomEvents.call(this);

            // init vars
            this.URI = {
                str: '',
                hasChanged: false
            };

            // start overlay
            initOverlay.call(this);

            // init selected node
            if (this.data !== null) {
                loadSelectedNode.call(this);
            }
        },

        setData = function(data) {
            this.data = data;
            this.sandbox.dom.data(this.$el, 'single-internal-link', this.data);
        },

        bindCustomEvents = function() {
            this.sandbox.on('husky.overlay.single-internal-link.' + this.options.instanceName + '.initialized', initColumnNavigation.bind(this));

            this.sandbox.on('husky.column-navigation.' + this.options.instanceName + '.action', function(item) {
                setData.call(this, item.id);
                loadSelectedNode.call(this);
                this.sandbox.emit(DATA_CHANGED.call(this), this.data, this.$el);

                this.sandbox.emit('husky.overlay.single-internal-link.' + this.options.instanceName + '.close');
            }.bind(this));

            // adjust position of overlay after column-navigation has initialized
            this.sandbox.on('husky.column-navigation.'+ this.options.instanceName +'.initialized', function() {
                this.sandbox.emit('husky.overlay.single-internal-link.' + this.options.instanceName + '.set-position');
            }.bind(this));
        },

        initOverlay = function() {
            var $element = this.sandbox.dom.createElement('<div/>');

            this.sandbox.dom.append(this.$el, $element);
            this.sandbox.start([
                {
                    name: 'overlay@husky',
                    options: {
                        triggerEl: this.$button,
                        cssClass: 'single-internal-overlay',
                        el: $element,
                        container: this.$el,
                        removeOnClose: false,
                        instanceName: 'single-internal-link.' + this.options.instanceName,
                        skin: 'wide',
                        slides: [
                            {
                                title: this.sandbox.translate(this.options.translations.overlayTitle),
                                data: templates.data(this.options),
                                buttons: [
                                    {
                                        type: 'cancel'
                                    }
                                ]
                            }
                        ]
                    }
                }
            ]);
        },

        /**
         * initialize column navigation
         */
        initColumnNavigation = function() {
            var url = getUrl(this.options.columnNavigationUrl, this.data);

            this.sandbox.start(
                [
                    {
                        name: 'column-navigation@husky',
                        options: {
                            el: getId.call(this, 'columnNavigation'),
                            selected: this.data,
                            url: url,
                            instanceName: this.options.instanceName,
                            actionIcon: 'fa-plus-circle',
                            resultKey: this.options.resultKey,
                            showOptions: false,
                            showStatus: false,
                            responsive: false,
                            sortable: false,
                            skin: 'fixed-height-small'
                        }
                    }
                ]
            );
        },

        loadSelectedNode = function() {
            this.sandbox.util.load(getUrl(this.options.url, this.data)).then(function(data) {
                this.$input.val((data.title || this.sandbox.translate(this.options.translations.noTitle)) + ' (' + (data.path || '/') + ')');
            }.bind(this));
        },

        getUrl = function(url, data) {
            return url.replace('{/uuid}', (!!data ? '/' + data : ''));
        };

    return {
        initialize: function() {
            // extend default options
            this.options = this.sandbox.util.extend({}, defaults, this.options);

            render.call(this);
        }
    };
});
