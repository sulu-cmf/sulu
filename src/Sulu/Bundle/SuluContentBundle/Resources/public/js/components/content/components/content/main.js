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

    return {

        header: function() {
            return {

                tabs: {
                    url: '/admin/content/navigation/content'
                },

                toolbar: {
                    parentTemplate: 'default',
                    template: [
                        {
                            'id': 'state',
                            'group': 'left',
                            'position': 100,
                            'type': 'select'
                        },
                        {
                            id: 'template',
                            icon: 'brush',
                            iconSize: 'large',
                            group: 'left',
                            position: 10,
                            type: 'select',
                            title: '',
                            hidden: true,
                            itemsOption: {
                                url: '/admin/content/template',
                                titleAttribute: 'template',
                                idAttribute: 'template',
                                translate: true,
                                languageNamespace: 'template.',
                                callback: function(item) {
                                    this.sandbox.emit('sulu.dropdown.template.item-clicked', item);
                                }.bind(this)
                            }
                        },
                        {
                            id: 'language',
                            iconSize: 'large',
                            group: 'right',
                            position: 10,
                            type: 'select',
                            title: '',
                            hidden: true,
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
                        }
                    ]
                }
            };
        }
    };
});
