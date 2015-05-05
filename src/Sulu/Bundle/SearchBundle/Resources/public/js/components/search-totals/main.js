/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class SearchTotals
 * @constructor
 */
define(['text!sulusearch/components/search-totals/main.html'], function(mainTemplate) {

    'use strict';

    var defaults = {
            instanceName: null,
            allCategory: 'all',
            categories: {}
        },

        /**
         * prefix for eventnames
         * @type {string}
         */
        prefix = 'sulu.search-totals.',

        /**
         * create an eventname with given postfix
         * @param postfix
         * @returns {string}
         */
        createEventName = function(postfix) {
            return prefix + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        },

        /**
         * update component with new totals and actove category
         * @event sulu.search-totals.[INSTANCE_NAME].update
         */
        UPDATE = function() {
            return createEventName.call(this, 'update');
        };

    return {
        /**
         * @method initialize
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.mainTemplate = this.sandbox.util.template(mainTemplate);

            this.bindCustomEvents();
            this.bindDomEvents();
        },

        /**
         * @method bindCustomEvents
         */
        bindCustomEvents: function() {
            this.sandbox.on(UPDATE.call(this), function(data, category) {
                this.data = data;
                this.activeCategory = category || this.options.allCategory;
                this.render();
            }.bind(this));
        },

        /**
         * @method bindDomEvents
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                var $element = this.sandbox.dom.find(event.currentTarget),
                    category = this.sandbox.dom.data($element, 'category');

                this.sandbox.emit('sulu.dropdown-input.searchResults.set', this.options.categories[category]);

                return false;
            }.bind(this), '.category-link');
        },

        /**
         * render component
         * @method render
         */
        render: function() {
            var template = '';
            if (this.activeCategory === this.options.allCategory && this.getTotal() > 0) {
                template = this.mainTemplate({
                    data: this.data,
                    categories: this.options.categories,
                    activeCategory: this.activeCategory,
                    translate: this.sandbox.translate
                });
            }

            this.$el.html(template);
        },

        /**
         * returns current total overall categories
         * @method getTotal
         */
        getTotal: function() {
            return _.reduce(this.data, function(memo, total) {
                return memo + total;
            }, 0);
        }
    };
});
