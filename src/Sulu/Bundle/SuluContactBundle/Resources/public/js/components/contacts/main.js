/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['sulucontact/model/contact'], function(Contact) {

    'use strict';

    return {

        initialize: function() {

            if (this.options.display === 'list') {
                this.renderList();
            } else if (this.options.display === 'form') {
                this.renderForm();
            } else {
                throw 'display type wrong';
            }

//            this.sandbox.on('sulu.contact.save', this.save, this)
        },

        renderList: function() {

            this.sandbox.start([
                {name: 'contacts/components/list@sulucontact', options: { el: this.$el}}
            ]);

            // wait for navigation events
            this.sandbox.on('sulu.contacts.load', function(id) {
                this.sandbox.emit('sulu.router.navigate', 'contacts/people/edit:' + id);
            }, this);

            this.sandbox.on('sulu.contacts.new', function() {
                this.sandbox.emit('sulu.router.navigate', 'contacts/people/add');
            }, this);

        },


        renderForm: function() {

            if (!!this.options.id) {
                var contactModel = new Contact();
                contactModel.set({id: this.options.id});
                contactModel.fetch({
                    success: function(model) {
                        this.sandbox.start([
                            {name: 'contacts/components/form@sulucontact', options: { el: this.$el, data: model.toJSON()}}
                        ]);
                    }.bind(this)
                });
            } else {
                this.sandbox.start([
                    {name: 'contacts/components/form@sulucontact', options: { el: this.$el}}
                ]);
            }
        }





    };
});
