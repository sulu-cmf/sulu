/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([
    'app-config',
    'sulusecurity/components/users/models/user',
    'services/husky/url-validator'
], function(AppConfig, User, urlValidator) {

    'use strict';

    /**
     * node type constant for content
     * @type {number}
     */
    var TYPE_CONTENT = 1,

        /**
         * node type constant for internal
         * @type {number}
         */
        TYPE_INTERNAL = 2,

        /**
         * node type constant for external
         * @type {number}
         */
        TYPE_EXTERNAL = 4;

    return {

        layout: function() {
            return {
                extendExisting: true,
                content: {
                    width: 'fixed',
                    leftSpace: true,
                    rightSpace: true
                }
            };
        },

        initialize: function() {
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);
            this.load();

            this.bindCustomEvents();
        },

        startComponents: function() {
            var languages = this.sandbox.dom.data('#shadow_base_language_select', 'languages'),
                shadowsForSelect = [],
                existingShadowForCurrentLanguage = null,
                selectedLanguage;

            if (this.data.enabledShadowLanguages[this.options.language] !== undefined) {
                existingShadowForCurrentLanguage = this.data.enabledShadowLanguages[this.options.language];
            }

            // if there are no languages for whatever reason, show
            // an empty, disabled selection (otherwise the select is broken)
            // note that this is an edge case
            if (languages.length === 0) {
                shadowsForSelect.push({
                    id: '',
                    name: 'no languages',
                    disabled: true
                });
            } else {
                this.sandbox.util.each(this.data.concreteLanguages, function(i, language) {
                    if (this.options.language === language) {
                        return;
                    }

                    var disabled = false;
                    if (existingShadowForCurrentLanguage === language) {
                        disabled = true;
                    }
                    shadowsForSelect.push({
                        id: language,
                        name: language,
                        disabled: disabled
                    });
                }.bind(this));
            }

            selectedLanguage = this.data.shadowBaseLanguage;

            // show at least a message
            if (shadowsForSelect.length === 0) {
                shadowsForSelect = [
                    {
                        id: -1,
                        name: this.sandbox.translate('sulu.content.form.settings.shadow.no_base_language'),
                        disabled: true
                    }
                ];
            }

            this.sandbox.start([
                {
                    name: 'select@husky',
                    options: {
                        el: '#shadow_base_language_select',
                        instanceName: 'settings',
                        multipleSelect: false,
                        defaultLabel: this.sandbox.translate('sulu.content.form.settings.shadow.select_base_language'),
                        data: shadowsForSelect,
                        preSelectedElements: [selectedLanguage]
                    }
                }
            ]);
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);

            // set header bar unsaved
            var changedEvent = function() {
                this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this);

            // hear for changing navigation contexts
            this.sandbox.on('husky.select.nav-contexts.selected.item', changedEvent.bind(this));
            this.sandbox.on('husky.select.nav-contexts.deselected.item', changedEvent.bind(this));

            this.sandbox.on('husky.select.settings.selected.item', function() {
                this.sandbox.emit('sulu.content.changed');
                this.sandbox.emit('sulu.content.contents.set-header-bar', false);
            }.bind(this));
        },

        bindDomEvents: function() {
            this.sandbox.dom.on('#content-type-container', 'change', function(e) {
                var $checkbox = this.sandbox.dom.$(e.currentTarget),
                    $form = this.sandbox.dom.find('.sub-form', $checkbox.parent().parent().parent()),
                    type = this.sandbox.dom.val($checkbox);

                this.sandbox.dom.hide('#content-type-container .sub-form');
                this.sandbox.dom.show($form);

                if (parseInt(type) === TYPE_CONTENT || !!this.data.shadowOn) {
                    this.sandbox.dom.show('#shadow-container');
                } else {
                    this.sandbox.dom.hide('#shadow-container');
                }
            }.bind(this), '.content-type');
        },

        updateTabVisibilityForShadowCheckbox: function(isInitial) {
            var checkboxEl = this.sandbox.dom.find('#shadow_on_checkbox')[0],
                action = checkboxEl.checked ? 'hide' : 'show';

            var tabAction = action;
            if (false === isInitial) {
                tabAction = 'hide';
            }

            if (tabAction === 'hide') {
                this.sandbox.util.each(['content', 'seo', 'excerpt'], function(i, tabName) {
                    this.sandbox.emit('husky.tabs.header.item.' + tabAction, 'tab-' + tabName);
                }.bind(this));

                this.sandbox.emit('husky.toolbar.header.item.disable', 'state', false);
            } else {
                this.sandbox.emit('husky.toolbar.header.item.enable', 'state', false);
            }

            this.sandbox.util.each(['show-in-navigation-container', 'settings-content-form-container'], function(i, formGroupId) {
                if (action === 'hide') {
                    this.sandbox.dom.find('#' + formGroupId).hide();
                } else {
                    this.sandbox.dom.find('#' + formGroupId).show();
                }
            }.bind(this));
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;

            require(['text!/admin/content/template/content/settings.html?webspaceKey=' + this.options.webspace + '&languageCode=' + this.options.language], function(template) {
                this.sandbox.dom.html(this.$el, this.sandbox.util.template(template, {
                    translate: this.sandbox.translate
                }));

                this.buildAllNavContexts(this.sandbox.dom.data('#nav-contexts', 'auraData'));

                this.bindDomEvents();
                this.setData(this.data);
                this.listenForChange();
                this.startComponents();
                this.sandbox.start(this.$el, {reset: true});

                this.sandbox.dom.on('#shadow_on_checkbox', 'click', function() {
                    this.updateTabVisibilityForShadowCheckbox(false);
                }.bind(this));

                this.updateTabVisibilityForShadowCheckbox(true);

                this.updateChangelog(data);

                this.sandbox.emit('sulu.preview.initialize');
            }.bind(this));
        },

        buildAllNavContexts: function(navContexts) {
            this.allNavContexts = {};

            for (var i = 0, len = navContexts.length; i < len; i++) {
                this.allNavContexts[navContexts[i].id] = navContexts[i].name;
            }
        },

        updateChangelog: function(data) {
            var setCreator = function(fullName) {
                    this.sandbox.dom.text('#created .name', fullName);
                    creatorDef.resolve();
                },
                setChanger = function(fullName) {
                    this.sandbox.dom.text('#changed .name', fullName);
                    changerDef.resolve();
                },
                creator, creatorDef = this.sandbox.data.deferred(),
                changer, changerDef = this.sandbox.data.deferred();

            if (data.creator === data.changer) {
                creator = new User({id: data.creator});

                creator.fetch({
                    global: false,

                    success: function(model) {
                        setChanger.call(this, model.get('fullName'));
                        setCreator.call(this, model.get('fullName'));
                    }.bind(this),

                    error: function() {
                        setChanger.call(this, this.sandbox.translate('sulu.content.form.settings.changelog.user-not-found'));
                        setCreator.call(this, this.sandbox.translate('sulu.content.form.settings.changelog.user-not-found'));
                    }.bind(this)
                });
            } else {
                creator = new User({id: data.creator});
                changer = new User({id: data.changer});

                creator.fetch({
                    global: false,

                    success: function(model) {
                        setCreator.call(this, model.get('fullName'));
                    }.bind(this),

                    error: function() {
                        setCreator.call(this, this.sandbox.translate('sulu.content.form.settings.changelog.user-not-found'));
                    }.bind(this)
                });

                changer.fetch({
                    global: false,

                    success: function(model) {
                        setChanger.call(this, model.get('fullName'));
                    }.bind(this),

                    error: function() {
                        setChanger.call(this, this.sandbox.translate('sulu.content.form.settings.changelog.user-not-found'));
                    }.bind(this)
                });
            }

            this.sandbox.dom.text('#created .date', this.sandbox.date.format(data.created, true));
            this.sandbox.dom.text('#changed .date', this.sandbox.date.format(data.changed, true));

            this.sandbox.data.when([creatorDef, changerDef]).then(function() {
                this.sandbox.dom.show('#changelog-container');
            }.bind(this));
        },

        setData: function(data) {
            var type = parseInt(data.nodeType);

            if (type === TYPE_CONTENT) {
                this.sandbox.dom.attr('#content-node-type', 'checked', true).trigger('change');
            } else if (type === TYPE_INTERNAL) {
                this.sandbox.dom.attr('#internal-link-node-type', 'checked', true).trigger('change');
            } else if (type === TYPE_EXTERNAL) {
                this.sandbox.dom.attr('#external-link-node-type', 'checked', true).trigger('change');
            }

            if (!!data.title) {
                this.sandbox.dom.val('#internal-title', data.title);
                this.sandbox.dom.val('#external-title', data.title);
            }
            if (!!data.internal_link) {
                this.sandbox.dom.data('#internal-link', 'singleInternalLink', data.internal_link);
            }
            if (!!data.external) {
                this.sandbox.dom.data('#external', 'url-data', urlValidator.match(data.external));
            }

            // updated after init
            this.sandbox.on('husky.select.nav-contexts.initialize', function() {
                var navContextsValues = [], i, len;
                for (i = 0, len = data.navContexts.length; i < len; i++) {
                    navContextsValues.push(this.allNavContexts[data.navContexts[i]]);
                }

                this.sandbox.dom.data('#nav-contexts', 'selection', data.navContexts);
                this.sandbox.dom.data('#nav-contexts', 'selectionValues', navContextsValues);

                $('#nav-contexts').trigger('data-changed');
            }.bind(this));

            if (data.shadowOn) {
                this.sandbox.dom.attr('#shadow_on_checkbox', 'checked', true);
                this.sandbox.emit('husky.toolbar.header.item.disable', 'state', false);
            }
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.$el, 'keyup change', function() {
                this.setHeaderBar(false);
            }.bind(this), '.trigger-save-button');

            this.sandbox.on('sulu.single-internal-link.internal-link.data-changed', function() {
                this.setHeaderBar(false);
            }.bind(this));
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        submit: function(action) {
            this.sandbox.logger.log('save Model');

            var data = {},
                baseLanguages = this.sandbox.dom.data('#shadow_base_language_select', 'selectionValues');

            data.navContexts = this.sandbox.dom.data('#nav-contexts', 'selection');
            data.nodeType = parseInt(this.sandbox.dom.val('input[name="nodeType"]:checked'));
            data.shadowOn = this.sandbox.dom.prop('#shadow_on_checkbox', 'checked');

            if (data.nodeType === TYPE_INTERNAL) {
                data.title = this.sandbox.dom.val('#internal-title');
                data.internal_link = this.sandbox.dom.data('#internal-link', 'singleInternalLink');
            } else if (data.nodeType === TYPE_EXTERNAL) {
                data.title = this.sandbox.dom.val('#external-title');
                data.external = this.sandbox.dom.data('#external', 'url-data').url;
            }

            if (!!baseLanguages && baseLanguages.length > 0) {
                data.shadowBaseLanguage = baseLanguages[0];
            }

            this.data = this.sandbox.util.extend(true, {}, this.data, data);

            // nav contexts not extend
            this.data.navContexts = data.navContexts;

            this.sandbox.emit('sulu.content.contents.save', this.data, action);
        }
    };
});
