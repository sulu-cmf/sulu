/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define([], function() {

    'use strict';

    var defaults = {
        maxDescriptionCharacters: 155,
        maxKeywords: 5,
        keywordsSeparator: ',',
        excerptUrlPrefix: 'www.yoursite.com'
    };

    return {
        view: true,

        layout: {
            changeNothing: true
        },

        templates: ['/admin/content/template/content/seo'],

        initialize: function() {
            this.options = this.sandbox.util.extend(true, {}, defaults, this.options);
            this.sandbox.emit('sulu.app.ui.reset', { navigation: 'small', content: 'auto'});
            this.sandbox.emit('husky.toolbar.header.item.disable', 'template', false);

            this.description = {
                $el: null,
                $counter: null
            };
            this.keywords = {
                $el: null,
                $counter: null,
                count: 0
            };

            this.formId = '#seo-form';
            this.load();
            this.bindCustomEvents();
            this.bindDomEvents();
        },

        bindCustomEvents: function() {
            // content save
            this.sandbox.on('sulu.toolbar.save', function(action) {
                this.submit(action);
            }, this);
        },

        bindDomEvents: function() {
            this.sandbox.dom.on(this.$el, 'keyup', this.updateExcerpt.bind(this));
        },

        submit: function(action) {
            this.sandbox.logger.log('save Model');
            if (this.sandbox.form.validate(this.formId)) {
                this.data.ext.seo = this.sandbox.form.getData(this.formId);
                this.sandbox.emit('sulu.content.contents.save', this.data, action);
            }
        },

        load: function() {
            // get content data
            this.sandbox.emit('sulu.content.contents.get-data', function(data) {
                this.render(data);
            }.bind(this));
        },

        render: function(data) {
            this.data = data;
            this.sandbox.dom.html(this.$el, this.renderTemplate('/admin/content/template/content/seo', {
                siteUrl: this.options.excerptUrlPrefix + '/' + this.options.language + this.data.path
            }));

            this.createForm(this.initData(data));
            this.listenForChange();
        },

        initData: function(data) {
            return data.ext.seo;
        },

        createForm: function(data) {
            this.sandbox.form.create(this.formId).initialized.then(function() {
                this.sandbox.form.setData(this.formId, data).then(function() {
                    this.listenForChange();
                    this.updateExcerpt();
                    this.initializeDescriptionCounter();
                    this.initializeKeywordsCounter();

                    this.sandbox.emit('sulu.preview.initialize');
                }.bind(this));
            }.bind(this));
        },

        initializeKeywordsCounter: function() {
            this.keywords.$el = this.$find('#seo-keywords');
            this.keywords.$counter = this.$find('#keywords-left');
            this.updateKeywordsCounter();
            this.sandbox.dom.on(this.keywords.$el, 'keyup', this.updateKeywordsCounter.bind(this));
        },

        updateExcerpt: function() {
            // update title
            this.sandbox.dom.html(this.$find('#seo-excerpt-title'), this.sandbox.dom.val(this.$find('#seo-title')));
            // update url

            // update description
            this.sandbox.dom.html(
                this.$find('#seo-excerpt-description'),
                this.sandbox.dom.val(this.$find('#seo-description'))
            );
        },

        initializeDescriptionCounter: function() {
            this.description.$el = this.$find('#seo-description');
            this.description.$counter = this.$find('#description-left');
            this.updateDescriptionCounter();
            this.sandbox.dom.on(this.description.$el, 'keyup', this.updateDescriptionCounter.bind(this));
        },

        updateDescriptionCounter: function() {
            var charsLeft = this.options.maxDescriptionCharacters - this.sandbox.dom.val(this.description.$el).length;
            this.sandbox.dom.html(this.description.$counter, ' ' + charsLeft + ' ');
            this.toggleWarning(this.description.$counter.parent(), (charsLeft < 0));
        },

        updateKeywordsCounter: function() {
            var value = this.sandbox.dom.trim(
                    this.sandbox.dom.trim(this.sandbox.dom.val(this.keywords.$el)),
                    this.options.keywordsSeparator
                ),
                keywords = value.split(this.options.keywordsSeparator),
                keywordsLeft = this.options.maxKeywords;
            // remove empty entries
            keywords = keywords.filter(function(value) {
                return !!value;
            });
            this.keywords.count = keywords.length;
            keywordsLeft = keywordsLeft - this.keywords.count;
            this.sandbox.dom.html(this.keywords.$counter, keywordsLeft);
            this.toggleWarning(this.keywords.$counter.parent(), (keywordsLeft < 0));
        },

        listenForChange: function() {
            this.sandbox.dom.on(this.formId, 'keyup change', function() {
                this.setHeaderBar(false);
                this.contentChanged = true;
            }.bind(this), '.trigger-save-button');
        },

        setHeaderBar: function(saved) {
            this.sandbox.emit('sulu.content.contents.set-header-bar', saved);
        },

        toggleWarning: function($el, warn) {
            if (warn) {
                $el.addClass('seo-warning');
            } else {
                $el.removeClass('seo-warning');
            }
        }
    };
});
