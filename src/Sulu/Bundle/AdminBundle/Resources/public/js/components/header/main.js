/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */

/**
 * @class Header
 * @constructor
 *
 * @param {Object} [options] Configuration object
 * @param {String} [options.instanceName] name of the instance
 * @param {Object} [options.tabsData] data to pass to the tabs component. For data-structure markup see husky
 * @param {Object} [options.tabsParentOptions] The options-object of the tabs-parent-component. this options get merged into each tabs-component-option
 * @param {Object} [options.tabsOption] an object of options which gets merged into each tabs-component option
 * @param {String|Object} [options.tabsContainer] Selector or dom object to insert the the tabs-content into
 * @param {Object} [options.toolbarOptions] options to pass to the toolbar-component
 * @param {Array} [options.toolbarButtons] Array of arguments to pass to the sulu.buttons.get function to recieve the toolbar-buttons
 * @param {Boolean|Object} [options.toolbarLanguageChanger] If true a default-language changer will be displayed. Can be an object to build a custom language changer
 * @param {Function} [options.toolbarLanguageChanger.callback] callback to pass the clicked language-item to
 * @param {String} [options.toolbarLanguageChanger.preselected] id of the language selected at the beginning
 * @param {Boolean} [options.toolbarDisabled] if true the toolbar-component won't be initialized
 * @param {Boolean} [options.noBack] if true the back icon won't be displayed
 * @param {String} [options.scrollContainerSelector] determines the box which gets observed for hiding the tabs on scroll
 * @param {String} [options.scrollDelta] this much pixels must be scrolled before the tabs get hidden or shown
 */

define([], function() {

    'use strict';

    var defaults = {
            instanceName: '',
            tabsData: null,
            tabsParentOptions: {},
            tabsOption: {},
            toolbarOptions: {},
            tabsContainer: null,
            toolbarLanguageChanger: false,
            toolbarButtons: [],
            toolbarDisabled: false,
            noBack: false,
            scrollContainerSelector: '.content-column > .page',
            scrollDelta: 50 //px
        },

        constants = {
            componentClass: 'sulu-header',
            hasTabsClass: 'has-tabs',
            backClass: 'back',
            backIcon: 'chevron-left',
            toolbarClass: 'toolbar',
            tabsClass: 'tabs',
            tabsSelector: '.tabs-container',
            toolbarSelector: '.toolbar-container',
            rightSelector: '.right-container',
            languageChangerTitleSelector: '.language-changer .title',
            overflownClass: 'overflown',
            hideTabsClass: 'tabs-hidden',
            tabsContentClass: 'tabs-content',
            contentTitleClass: 'content-title',
            toolbarDefaults: {
                groups: [
                    {id: 'left', align: 'left'}
                ]
            },
            languageChangerDefaults: {
                instanceName: 'header-language',
                alignment: 'right',
                valueName: 'title'
            }
        },

        templates = {
            toolbarRow: [
                '<div class="blue-row"></div>',
                '<div class="toolbar-row">',
                '   <div class="left-container ' + constants.backClass + '">',
                '       <span class="fa-' + constants.backIcon + '"></span>',
                '   </div>',
                '   <div class="toolbar-container">',
                '       <div class="toolbar-wrapper">',
                '           <div class="' + constants.toolbarClass + '"></div>',
                '       </div>',
                '   </div>',
                '   <div class="right-container">',
                '   </div>',
                '</div>'
            ].join(''),
            tabsRow: [
                '<div class="tabs-row">',
                '    <div class="' + constants.tabsClass + '"></div>',
                '</div>'
            ].join(''),
            languageChanger: [
                '<div class="language-changer">',
                '   <span class="title"><%= title %></span>',
                '   <span class="dropdown-toggle"></span>',
                '</div>'
            ].join(''),
            titleElement: [
                '<h2 class="' + constants.contentTitleClass + '"><%= title %></h2>'
            ].join(''),
        },

        createEventName = function(postfix) {
            return 'sulu.header.' + ((!!this.options.instanceName) ? this.options.instanceName + '.' : '') + postfix;
        },

        /**
         * trigger after initialization has finished
         *
         * @event sulu.header.[INSTANCE_NAME].initialized
         */
        INITIALIZED = function() {
            return createEventName.call(this, 'initialized');
        },

        /**
         * emitted when the back-icon gets clicked
         *
         * @event sulu.header.[INSTANCE_NAME].back
         */
        BACK = function() {
            return createEventName.call(this, 'back');
        },

        /**
         * emited if the language changer got changed
         *
         * @event sulu.header.[INSTANCE_NAME].language-changed
         * @param {string} the language which got changed to
         */
        LANGUAGE_CHANGED = function() {
            return createEventName.call(this, 'language-changed');
        },

        /**
         * emited if switched to a tab with no specified component
         *
         * @event sulu.header.[INSTANCE_NAME].tab-changed
         * @param {Object} the tab item switched to
         */
        TAB_CHANGED = function() {
            return createEventName.call(this, 'tab-changed');
        },

    /*********************************************
     *   Abstract events
     ********************************************/

        /**
         * listens on activates tabs
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.activate
         */
        TABS_ACTIVATE = function() {
            return createEventName.call(this, 'tabs.activate');
        },

        /**
         * listens on deactivates tabs
         *
         * @event sulu.header.[INSTANCE_NAME].tabs.activate
         */
        TABS_DEACTIVATE = function() {
            return createEventName.call(this, 'tabs.deactivate');
        },

        /**
         * listens on and sets a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.button.set
         * @param {string} id The id of the button
         * @param {object} object with a icon and title
         */
        TOOLBAR_BUTTON_SET = function() {
            return createEventName.call(this, 'toolbar.button.set');
        },

        /**
         * listens on and sets an item in loading state
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.loading
         * @param {string} id The id of the item
         */
        TOOLBAR_ITEM_LOADING = function() {
            return createEventName.call(this, 'toolbar.item.loading');
        },

        /**
         * listens on and changes the item of a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.change
         * @param {string} button The id of the button
         * @param {string} item the id or the index of the dropdown-item
         */
        TOOLBAR_ITEM_CHANGE = function() {
            return createEventName.call(this, 'toolbar.item.change');
        },

        /**
         * listens on and marks a subitem
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.mark
         * @param {string} item The id of the subitem
         */
        TOOLBAR_ITEM_MARK = function() {
            return createEventName.call(this, 'toolbar.item.mark');
        },

        /**
         * listens on and shows a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.show
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_SHOW = function() {
            return createEventName.call(this, 'toolbar.item.show');
        },

        /**
         * listens on and hides a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.hide
         * @param {string} button The id of the button
         */
        TOOLBAR_ITEM_HIDE = function() {
            return createEventName.call(this, 'toolbar.item.hide');
        },

        /**
         * listens on and enables a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.enable
         * @param {string} button The id of the button
         * @param {Boolean} true to highlight the button on change
         */
        TOOLBAR_ITEM_ENABLE = function() {
            return createEventName.call(this, 'toolbar.item.enable');
        },

        /**
         * listens on and disables a button
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.item.enable
         * @param {string} button The id of the button
         * @param {Boolean} true to highlight the button on change
         */
        TOOLBAR_ITEM_DISABLE = function() {
            return createEventName.call(this, 'toolbar.item.disable');
        },

        /**
         * listens on and shows back icon
         *
         * @event sulu.header.[INSTANCE_NAME].toolbar.items.set
         * @param id {string|number} id of the parent item
         * @param items {array} array of items to set
         */
        TOOLBAR_ITEMS_SET = function() {
            return createEventName.call(this, 'toolbar.items.set');
        };

    return {
        /**
         * Initializes the component
         */
        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            // set default callback when no callback is provided

            // store the instance-name of the toolbar
            this.toolbarInstanceName = 'header' + this.options.instanceName;
            this.toolbarCollapsed = false;
            this.toolbarExpandedWidth = 0;
            this.oldScrollPosition = 0;
            this.$tabs = null;
            this.tabsAction = null;

            this.bindCustomEvents();
            this.render();
            this.bindDomEvents();

            var toolbarDef, tabsDef;
            toolbarDef = this.startToolbar();
            this.startLanguageChanger();
            tabsDef = this.startTabs();

            this.sandbox.data.when(toolbarDef, tabsDef).then(function() {
                this.sandbox.emit(INITIALIZED.call(this));
                this.oldScrollPosition = this.sandbox.dom.scrollTop(this.options.scrollContainerSelector);
            }.bind(this));
        },

        /**
         * Renders the component
         */
        render: function() {
            // add component-class
            this.sandbox.dom.addClass(this.$el, constants.componentClass);

            this.sandbox.dom.append(this.$el, this.sandbox.util.template(templates.toolbarRow)());
            this.sandbox.dom.append(this.$el, this.sandbox.util.template(templates.tabsRow)());

            // render title if there are no tabs (else they are rendered in tabChangeHandler)
            if (!!this.options.title && !this.options.tabsData) {
                this.sandbox.dom.prepend(this.options.tabsParentOption.el.parentElement, this.getTitleElement());
            }

            // hide back if configured
            if (this.options.noBack === true) {
                this.sandbox.dom.hide(this.$find('.' + constants.backClass));
            } else {
                this.sandbox.dom.show(this.$find('.' + constants.backClass));
            }
        },

        getTitleElement: function() {
            var title = (typeof this.options.title === 'function') ? this.options.title() : this.options.title;
            return this.sandbox.dom.createElement(this.sandbox.util.template(templates.titleElement)({
                title: this.sandbox.translate(title)
            }));
        },

        /**
         * Handles the start of the Tabs
         */
        startTabs: function() {
            var def = this.sandbox.data.deferred();

            if (!this.options.tabsData) {
                def.resolve();
            } else if (this.options.tabsData.length === 1) {
                def.resolve();
                this.tabChangedHandler(this.options.tabsData[0]);
            } else if (this.options.tabsData.length > 1) {
                this.startTabsComponent(def);
            } else {
                def.resolve();
            }

            return def;
        },

        /**
         * Starts the tabs component
         * @param {deferred} def
         */
        startTabsComponent: function(def) {
            if (!!this.options.tabsData) {
                var $container = this.sandbox.dom.createElement('<div/>'),
                    options = {
                        el: $container,
                        data: this.options.tabsData,
                        instanceName: 'header' + this.options.instanceName,
                        forceReload: false,
                        forceSelect: true,
                        fragment: this.sandbox.mvc.history.fragment
                    };

                this.sandbox.dom.addClass(this.$el, constants.hasTabsClass);

                // wait for initialized
                this.sandbox.once('husky.tabs.header.initialized', function() {
                    def.resolve();
                }.bind(this));

                this.sandbox.dom.html(this.$find('.' + constants.tabsClass), $container);

                this.sandbox.start([
                    {
                        name: 'tabs@husky',
                        options: options
                    }
                ]);
            }
        },

        /**
         * Handles the starting of the toolbar
         */
        startToolbar: function() {
            var def = this.sandbox.data.deferred();

            if (this.options.toolbarDisabled !== true) {
                var options = this.options.toolbarOptions;
                options = this.sandbox.util.extend(true, {}, constants.toolbarDefaults, options, {
                    buttons: this.sandbox.sulu.buttons.get.call(this, this.options.toolbarButtons)
                });
                // start toolbar component with built options
                this.startToolbarComponent(options, def);
            } else {
                def.resolve();
            }

            return def;
        },

        /**
         * Renderes and starts the language-changer dropdown
         */
        startLanguageChanger: function() {
            if (!!this.options.toolbarLanguageChanger) {
                var $element = this.sandbox.dom.createElement(this.sandbox.util.template(templates.languageChanger)({
                        title: this.options.toolbarLanguageChanger.preSelected || this.sandbox.sulu.user.locale
                    })),
                    options = constants.languageChangerDefaults;
                this.sandbox.dom.show(this.$find(constants.rightSelector));
                this.sandbox.dom.append(this.$find(constants.rightSelector), $element);
                options.el = $element;
                options.data = this.options.toolbarLanguageChanger.data || this.getDefaultLanguages();
                this.sandbox.start([{
                    name: 'dropdown@husky',
                    options: options
                }]);
            } else {
                this.sandbox.dom.hide(this.$find(constants.rightSelector));
            }
        },

        /**
         * Returns an array of objects with id and title property containing the system locales
         * @returns {Array}
         */
        getDefaultLanguages: function() {
            var items = [], i, length;
            for (i = -1, length = this.sandbox.sulu.locales.length; ++i < length;) {
                items.push({
                    id: this.sandbox.sulu.locales[i],
                    title: this.sandbox.sulu.locales[i]
                });
            }
            return items;
        },

        /**
         * Starts the husky-component
         * @param {object} options The options to pass to the toolbar component
         * @param {deferred} def
         */
        startToolbarComponent: function(options, def) {
            var $container = this.sandbox.dom.createElement('<div />'),
            // global default values
                componentOptions = {
                    el: $container,
                    skin: 'big',
                    instanceName: this.toolbarInstanceName
                };

            // wait for initialized
            if (!!def) {
                this.sandbox.once('husky.toolbar.' + this.toolbarInstanceName + '.initialized', function() {
                    def.resolve();
                }.bind(this));
            }

            this.sandbox.dom.html(this.$find('.' + constants.toolbarClass), $container);

            // merge default tabs-options with passed ones
            componentOptions = this.sandbox.util.extend(true, {}, componentOptions, options);

            this.sandbox.start([
                {
                    name: 'toolbar@husky',
                    options: componentOptions
                }
            ]);
        },

        /**
         * listens to tab events
         */
        bindCustomEvents: function() {
            this.sandbox.on('husky.toolbar.' + this.toolbarInstanceName + '.dropdown.opened', this.lockToolbarScroll.bind(this));
            this.sandbox.on('husky.toolbar.' + this.toolbarInstanceName + '.dropdown.closed', this.unlockToolbarScroll.bind(this));
            this.sandbox.on('husky.toolbar.' + this.toolbarInstanceName + '.button.changed', this.updateToolbarOverflow.bind(this));

            this.sandbox.on('husky.dropdown.header-language.item.click', this.languageChanged.bind(this));

            this.sandbox.on('husky.tabs.header.initialized', this.tabChangedHandler.bind(this));
            this.sandbox.on('husky.tabs.header.item.select', this.tabChangedHandler.bind(this));

            this.bindAbstractToolbarEvents();
            this.bindAbstractTabsEvents();
        },

        /**
         * Renderes and starts a tab-content component if specified. if not emits an event
         * @param tabItem {Object} the Tabs object
         */
        tabChangedHandler: function(tabItem) {
            if (!!tabItem.component) {
                var options;
                if (!tabItem.forceReload && tabItem.action === this.tabsAction) {
                    return false; // no reload required
                }
                this.tabsAction = tabItem.action;
                // resets store to prevent duplicated models
                if (!!tabItem.resetStore) {
                    this.sandbox.mvc.Store.reset();
                }
                this.stopTabContent();

                var $container = this.sandbox.dom.createElement('<div class="' + constants.tabsContentClass + '"/>');
                this.sandbox.dom.append(this.options.tabsContainer, $container);

                options = this.sandbox.util.extend(true, {},
                    this.options.tabsParentOption,
                    this.options.tabsOption,
                    {el: $container},
                    tabItem.componentOptions);

                this.sandbox.start([{
                    name: tabItem.component,
                    options: options
                }]).then(function(tabComponent) {
                    // render title if view title is set and tab-option noTitle title is false or not set
                    if (!!this.options.title && (!tabComponent.tabOptions || !tabComponent.tabOptions.noTitle)) {
                        this.sandbox.dom.prepend(this.options.tabsContainer, this.getTitleElement());
                    }
                }.bind(this));
            } else {
                this.sandbox.emit(TAB_CHANGED.call(this), tabItem);
            }
        },

        /**
         * Stops the tab-content-component
         */
        stopTabContent: function() {
            App.stop('.' + constants.tabsContentClass + ' *');
            App.stop('.' + constants.tabsContentClass);
            this.sandbox.dom.empty(this.options.tabsContainer);
        },

        /**
         * Handles the change of the language-changer
         * @param item
         */
        languageChanged: function(item) {
            this.sandbox.dom.html(this.$find(constants.languageChangerTitleSelector), item.title);
            this.sandbox.emit(LANGUAGE_CHANGED.call(this), item);
        },

        /**
         * Makes the toolbar unscrollable and makes the toolbar-overflow's overflow visible
         * so the dropdown can be seen
         */
        lockToolbarScroll: function() {
            var $container = this.$find(constants.toolbarSelector),
                scrollPos = this.sandbox.dom.scrollLeft($container);
            this.sandbox.dom.css($container, {overflow: 'visible'});
            this.sandbox.dom.css(this.sandbox.dom.children($container), {
                'margin-left': ((-1) * scrollPos) + 'px'
            });
        },

        /**
         * Makes the toolbar-container's overflow hidden and the wrapper itself scrollable
         */
        unlockToolbarScroll: function() {
            var $container = this.$find(constants.toolbarSelector);
            this.sandbox.dom.removeAttr($container, 'style');
            this.sandbox.dom.removeAttr(this.sandbox.dom.children($container), 'style');
        },

        /**
         * Abstracts husky-toolbar events
         */
        bindAbstractToolbarEvents: function() {
            this.sandbox.on(TOOLBAR_ITEMS_SET.call(this), function(id, items) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.items.set', id, items);
            }.bind(this));

            this.sandbox.on(TOOLBAR_BUTTON_SET.call(this), function(id, object) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.button.set', id, object);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_LOADING.call(this), function(id) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.loading', id);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_CHANGE.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.change', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_SHOW.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.show', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_HIDE.call(this), function(id, name) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.hide', id, name);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_ENABLE.call(this), function(id, highlight) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.enable', id, highlight);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_DISABLE.call(this), function(id, highlight) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.disable', id, highlight);
            }.bind(this));

            this.sandbox.on(TOOLBAR_ITEM_MARK.call(this), function(id) {
                this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.item.mark', id);
            }.bind(this));
        },

        /**
         * Abstracts husky-tabs events
         */
        bindAbstractTabsEvents: function() {
            this.sandbox.on(TABS_ACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header.deactivate');
            }.bind(this));

            this.sandbox.on(TABS_DEACTIVATE.call(this), function() {
                this.sandbox.emit('husky.tabs.header.activate');
            }.bind(this));
        },

        /**
         * Bind Dom-events
         */
        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'click', function() {
                this.sandbox.emit(BACK.call(this));
            }.bind(this), '.' + constants.backClass);

            this.sandbox.dom.on(this.sandbox.dom.window, 'resize', this.updateToolbarOverflow.bind(this));
            this.sandbox.dom.on(this.$el, 'click', this.updateToolbarOverflow.bind(this));
            if (!!this.options.tabsData) {
                this.sandbox.dom.on(this.options.scrollContainerSelector, 'scroll', this.scrollHandler.bind(this));
            }
        },

        /**
         * Handles the scroll event to hide or show the tabs
         */
        scrollHandler: function() {
            var scrollTop = this.sandbox.dom.scrollTop(this.options.scrollContainerSelector);
            if (scrollTop <= this.oldScrollPosition - this.options.scrollDelta || scrollTop < this.options.scrollDelta) {
                this.showTabs();
                this.oldScrollPosition = scrollTop;
            } else if (scrollTop >= this.oldScrollPosition + this.options.scrollDelta) {
                this.hideTabs();
                this.oldScrollPosition = scrollTop;
            }
        },

        /**
         * Depending on if the toolbar overflows or not collapses or expands the toolbar
         * collapsing - if the toolbar is expanded and overflown
         * expanding - if the toolbar is underflown and collapsed and the expanded version has enough space
         */
        updateToolbarOverflow: function() {
            var $container = this.$find(constants.toolbarSelector);
            if (this.sandbox.dom.width($container) < $container[0].scrollWidth) {
                if (this.toolbarCollapsed === false) {
                    this.toolbarExpandedWidth = this.sandbox.dom.outerWidth(this.sandbox.dom.children($container));
                    this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.collapse', function() {
                        this.toolbarCollapsed = true;
                        this.updatedToolbarOverflowClass();
                    }.bind(this));
                } else {
                    this.updatedToolbarOverflowClass();
                }
            } else {
                if (this.toolbarCollapsed === true && this.sandbox.dom.width($container) >= this.toolbarExpandedWidth) {
                    this.sandbox.emit('husky.toolbar.' + this.toolbarInstanceName + '.expand', function() {
                        this.toolbarExpandedWidth = this.sandbox.dom.outerWidth(this.sandbox.dom.children($container));
                        this.toolbarCollapsed = false;
                        this.updatedToolbarOverflowClass();
                    }.bind(this));
                } else {
                    this.updatedToolbarOverflowClass();
                }
            }
        },

        /**
         * Sets an overflow-class on the toolbar, depending on whether or ot
         * the toolbar overflows
         */
        updatedToolbarOverflowClass: function() {
            var $container = this.$find(constants.toolbarSelector);
            if (this.sandbox.dom.width($container) < $container[0].scrollWidth) {
                this.sandbox.dom.addClass($container, constants.overflownClass);
            } else {
                this.sandbox.dom.removeClass($container, constants.overflownClass);
            }
        },

        /**
         * Hides the tabs
         */
        hideTabs: function() {
            this.sandbox.dom.addClass(this.$el, constants.hideTabsClass);
        },

        /**
         * Shows the tabs
         */
        showTabs: function() {
            this.sandbox.dom.removeClass(this.$el, constants.hideTabsClass);
        }
    };
});
