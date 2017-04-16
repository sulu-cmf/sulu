/*
 * This file is part of the Husky Validation.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

define([
    'type/default'
], function(Default) {

    'use strict';

    return function($el, options) {
        var defaults = {},

            subType = {
                setValue: function(value) {
                    App.dom.data($el, 'value', value).trigger('data-changed');
                },

                getValue: function() {
                    return App.dom.data($el, 'value');
                },

                needsValidation: function() {
                    return this.$el.data('auraContentId') !== 'index';
                },

                validate: function() {
                    if (!this.needsValidation()) {
                        return true;
                    }

                    var val = this.getValue(),
                        part = App.dom.data($el, 'part');

                    return part.length > 0 && val !== '/';
                }
            };

        return new Default($el, defaults, options, 'resourceLocator', subType);
    };
});
