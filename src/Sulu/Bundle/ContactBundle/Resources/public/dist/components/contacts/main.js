define(["sulucontact/model/contact","sulucontact/model/activity","sulucontact/model/title","sulucontact/model/position","sulucategory/model/category"],function(a,b,c,d,e){"use strict";return{initialize:function(){if(this.bindCustomEvents(),this.bindSidebarEvents(),"list"===this.options.display)this.renderList();else if("form"===this.options.display)this.renderForm();else if("activities"===this.options.display)this.renderActivities();else{if("documents"!==this.options.display)throw"display type wrong";this.renderComponent("",this.options.display,"documents-form",{type:"contact"})}},bindCustomEvents:function(){this.sandbox.once("sulu.contacts.activities.set.defaults",this.parseActivityDefaults.bind(this)),this.sandbox.on("sulu.contacts.activities.get.defaults",function(){this.sandbox.emit("sulu.contacts.activities.set.defaults",this.activityDefaults)},this),this.sandbox.on("sulu.contacts.contact.delete",function(){this.del()},this),this.sandbox.on("sulu.contacts.contacts.save",function(a){this.save(a)},this),this.sandbox.on("sulu.contacts.contacts.load",function(a){this.load(a)},this),this.sandbox.on("sulu.contacts.contacts.new",function(){this.add()},this),this.sandbox.on("sulu.contacts.contacts.delete",function(a){this.delContacts(a)},this),this.sandbox.on("sulu.contacts.contacts.list",function(){this.sandbox.emit("sulu.router.navigate","contacts/contacts")},this),this.sandbox.on("sulu.contacts.contact.activities.delete",this.removeActivities.bind(this)),this.sandbox.on("sulu.contacts.contact.activity.save",this.saveActivity.bind(this)),this.sandbox.on("sulu.contacts.contact.activity.load",this.loadActivity.bind(this)),this.initializeDropDownListender("title-select","api/contact/titles"),this.initializeDropDownListender("position-select","api/contact/positions"),this.sandbox.on("sulu.contacts.contacts.medias.save",this.saveDocuments.bind(this))},saveDocuments:function(a,b,c){this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.processAjaxForDocuments(b,a,"POST"),this.processAjaxForDocuments(c,a,"DELETE")},processAjaxForDocuments:function(a,b,c){var d,e=[],f=[];a.length>0&&(this.sandbox.util.each(a,function(a,g){"DELETE"===c?d="/admin/api/contacts/"+b+"/medias/"+g:"POST"===c&&(d="/admin/api/contacts/"+b+"/medias"),e.push(this.sandbox.util.ajax({url:d,data:{mediaId:g},type:c}).fail(function(){this.sandbox.logger.error("Error while saving documents!")}.bind(this))),f.push(g)}.bind(this)),this.sandbox.util.when.apply(null,e).then(function(){"DELETE"===c?(this.sandbox.logger.warn(f),this.sandbox.emit("sulu.contacts.accounts.medias.removed",f)):"POST"===c&&(this.sandbox.logger.warn(f),this.sandbox.emit("sulu.contacts.accounts.medias.saved",f))}.bind(this)))},bindSidebarEvents:function(){this.sandbox.dom.off("#sidebar"),this.sandbox.dom.on("#sidebar","click",function(a){var b=this.sandbox.dom.data(a.currentTarget,"id");this.sandbox.emit("sulu.contacts.contacts.load",b)}.bind(this),"#sidebar-contact-list"),this.sandbox.dom.on("#sidebar","click",function(a){var b=this.sandbox.dom.data(a.currentTarget,"id");this.sandbox.emit("sulu.router.navigate","contacts/accounts/edit:"+b+"/details"),this.sandbox.emit("husky.navigation.select-item","contacts/accounts")}.bind(this),"#main-account")},parseActivityDefaults:function(a){var b,c;for(b in a)if(a.hasOwnProperty(b))for(c in a[b])a[b].hasOwnProperty(c)&&(a[b][c].translation=this.sandbox.translate(a[b][c].name));this.activityDefaults=a},removeActivities:function(a){this.confirmDeleteDialog(function(c){if(c){var d;this.sandbox.util.foreach(a,function(a){d=b.findOrCreate({id:a}),d.destroy({success:function(){this.sandbox.emit("sulu.contacts.contact.activity.removed",a)}.bind(this),error:function(){this.sandbox.logger.log("error while deleting activity")}.bind(this)})}.bind(this))}}.bind(this))},saveActivity:function(a){var c=!0;a.id&&(c=!1),this.activity=b.findOrCreate({id:a.id}),this.activity.set(a),this.activity.save(null,{success:function(a){this.activity=this.flattenActivityObjects(a.toJSON()),this.activity.assignedContact=this.activity.assignedContact.fullName,c?this.sandbox.emit("sulu.contacts.contact.activity.added",this.activity):this.sandbox.emit("sulu.contacts.contact.activity.updated",this.activity)}.bind(this),error:function(){this.sandbox.logger.log("error while saving activity")}.bind(this)})},flattenActivityObjects:function(a){return a.activityStatus&&(a.activityStatus=this.sandbox.translate(a.activityStatus.name)),a.activityType&&(a.activityType=this.sandbox.translate(a.activityType.name)),a.activityPriority&&(a.activityPriority=this.sandbox.translate(a.activityPriority.name)),a},loadActivity:function(a){a?(this.activity=b.findOrCreate({id:a}),this.activity.fetch({success:function(a){this.activity=a,this.sandbox.emit("sulu.contacts.contact.activity.loaded",a.toJSON())}.bind(this),error:function(a,b){this.sandbox.logger.log("error while fetching activity",a,b)}.bind(this)})):this.sandbox.logger.warn("no id given to load activity")},del:function(){this.confirmDeleteDialog(function(a){a&&(this.sandbox.emit("sulu.header.toolbar.item.loading","options-button"),this.contact.destroy({success:function(){this.sandbox.emit("sulu.router.navigate","contacts/contacts")}.bind(this)}))}.bind(this))},save:function(a){this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.contact.set(a),this.contact.get("categories").reset(),this.sandbox.util.foreach(a.categories,function(a){var b=e.findOrCreate({id:a});this.contact.get("categories").add(b)}.bind(this)),this.contact.save(null,{success:function(b){var c=b.toJSON();a.id?this.sandbox.emit("sulu.contacts.contacts.saved",c):(this.sandbox.emit("sulu.content.saved"),this.sandbox.emit("sulu.router.navigate","contacts/contacts/edit:"+c.id+"/details"))}.bind(this),error:function(){this.sandbox.logger.log("error while saving profile")}.bind(this)})},load:function(a){this.sandbox.emit("sulu.router.navigate","contacts/contacts/edit:"+a+"/details")},add:function(){this.sandbox.emit("sulu.router.navigate","contacts/contacts/add")},delContacts:function(b){return b.length<1?void this.sandbox.emit("sulu.dialog.error.show","No contacts selected for Deletion"):void this.confirmDeleteDialog(function(c){c&&b.forEach(function(b){var c=new a({id:b});c.destroy({success:function(){this.sandbox.emit("husky.datagrid.record.remove",b)}.bind(this)})}.bind(this))}.bind(this))},renderList:function(){var a=this.sandbox.dom.createElement('<div id="contacts-list-container"/>');this.html(a),this.sandbox.start([{name:"contacts/components/list@sulucontact",options:{el:a}}])},renderForm:function(){this.contact=new a;var b=this.sandbox.dom.createElement('<div id="contacts-form-container"/>');this.html(b),this.options.id?(this.contact=new a({id:this.options.id}),this.contact.fetch({success:function(a){this.sandbox.start([{name:"contacts/components/form@sulucontact",options:{el:b,data:a.toJSON()}}])}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact")}.bind(this)})):this.sandbox.start([{name:"contacts/components/form@sulucontact",options:{el:b,data:this.contact.toJSON()}}])},renderActivities:function(){var b;this.contact=new a,b=this.sandbox.dom.createElement('<div id="activities-list-container"/>'),this.html(b),this.dfdContact=this.sandbox.data.deferred(),this.dfdSystemContacts=this.sandbox.data.deferred(),this.options.id?(this.getContact(this.options.id),this.getSystemMembers(),this.sandbox.data.when(this.dfdContact,this.dfdSystemContacts).then(function(){this.sandbox.start([{name:"activities@sulucontact",options:{el:b,contact:this.contact.toJSON(),responsiblePersons:this.responsiblePersons,instanceName:"contact",widgetUrl:"/admin/widget-groups/contact-detail?contact="}}])}.bind(this))):this.sandbox.logger.error("activities are not available for unsaved contacts!")},renderComponent:function(b,c,d,e){var f=this.sandbox.dom.createElement('<div id="'+d+'"/>'),g=this.sandbox.data.deferred();return this.html(f),this.options.id&&(this.contact=new a({id:this.options.id}),this.contact.fetch({success:function(a){this.contact=a,this.sandbox.start([{name:b+c+"@sulucontact",options:{el:f,data:a.toJSON(),params:e?e:{}}}]),g.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact"),g.reject()}.bind(this)})),g.promise()},getContact:function(b){this.contact=new a({id:b}),this.contact.fetch({success:function(a){this.contact=a,this.dfdContact.resolve()}.bind(this),error:function(){this.sandbox.logger.log("error while fetching contact")}.bind(this)})},getSystemMembers:function(){this.sandbox.util.load("api/contacts?bySystem=true").then(function(b){this.responsiblePersons=b._embedded.contacts,this.sandbox.util.foreach(this.responsiblePersons,function(b){var c=a.findOrCreate(b);b=c.toJSON()}.bind(this)),this.dfdSystemContacts.resolve()}.bind(this)).fail(function(a,b){this.sandbox.logger.error(a,b)}.bind(this))},itemDeleted:function(a,b){a&&a.length>0&&this.sandbox.util.each(a,function(a,c){this.deleteItem(c,b)}.bind(this))},deleteItem:function(a,b){"title-select"===b?this.deleteEntity(c.findOrCreate({id:a}),b):"position-select"===b&&this.deleteEntity(d.findOrCreate({id:a}),b)},deleteEntity:function(a,b){a.destroy({error:function(){this.sandbox.emit("husky.select."+b+".revert")}.bind(this)})},itemSaved:function(a,b,c){a&&a.length>0&&this.sandbox.util.save(b,"PATCH",a).then(function(a){this.sandbox.emit(c+".update",a,null,!0,!0)}.bind(this)).fail(function(a,b){this.sandbox.emit(c+".revert"),this.sandbox.logger.error(a,b)}.bind(this))},initializeDropDownListender:function(a,b){var c="husky.select."+a;this.sandbox.on(c+".delete",function(b){this.itemDeleted(b,a)}.bind(this)),this.sandbox.on(c+".save",function(a){this.itemSaved(a,b,c)}.bind(this))},confirmDeleteDialog:function(a){if(a&&"function"!=typeof a)throw"callback is not a function";this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","sulu.overlay.delete-desc",a.bind(this,!1),a.bind(this,!0))}}});