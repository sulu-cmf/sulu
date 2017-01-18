define(["config","services/sulucontact/account-manager"],function(a,b){"use strict";var c=["urls","emails","faxes","phones","notes","addresses"],d={tagsId:"#tags",addressAddId:"#address-add",bankAccountAddId:"#bank-account-add",addAddressWrapper:".grid-row",addBankAccountsWrapper:".grid-row",editFormSelector:"#contact-edit-form",formSelector:"#account-form",formContactFields:"#contact-fields",logoImageId:"#image-content",logoDropzoneSelector:"#image-dropzone",logoDeleteSelector:"#image-delete",avatarDownloadSelector:"#image-download",logoThumbnailFormat:"sulu-400x400-inset"},e={addBankAccountsIcon:['<div class="grid-row">','   <div class="grid-col-12">','       <div id="bank-account-add" class="addButton bank-account-add m-left-140"></div>',"   </div>","</div>"].join(""),addAddressesIcon:['<div class="grid-row">','   <div class="grid-col-12">','       <div id="address-add" class="addButton address-add m-left-140"></div>',"   </div>","</div>"].join("")};return{tabOptions:{noTitle:!0},layout:function(){return{content:{width:"max",leftSpace:!1,rightSpace:!1}}},templates:["/admin/contact/template/account/form"],initialize:function(){this.data=this.options.data(),this.formOptions=a.get("sulu.contact.form"),this.autoCompleteInstanceName="contacts-",this.dfdListenForChange=this.sandbox.data.deferred(),this.dfdFormIsSet=this.sandbox.data.deferred(),this.render(),this.listenForChange()},destroy:function(){this.sandbox.emit("sulu.header.toolbar.item.hide","disabler"),this.cleanUp()},render:function(){this.sandbox.emit("sulu.header.toolbar.item.show","disabler"),this.sandbox.once("sulu.contacts.set-defaults",this.setDefaults.bind(this)),this.sandbox.once("sulu.contacts.set-types",this.setTypes.bind(this)),this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/contact/template/account/form",{categoryLocale:this.sandbox.sulu.getDefaultContentLocale()}));var a=this.initAccountData();this.initForm(a),this.initLogoContainer(a),this.setTags(),this.bindCustomEvents(),this.bindTagEvents(a)},setTags:function(){var a=this.sandbox.util.uniqueId();this.data.id&&(a+="-"+this.data.id),this.autoCompleteInstanceName+=a,this.dfdFormIsSet.then(function(){this.sandbox.start([{name:"auto-complete-list@husky",options:{el:"#tags",instanceName:this.autoCompleteInstanceName,getParameter:"search",itemsKey:"tags",remoteUrl:"/admin/api/tags?flat=true&sortBy=name&searchFields=name",completeIcon:"tag",noNewTags:!0}}])}.bind(this))},bindTagEvents:function(a){a.tags&&a.tags.length>0?(this.sandbox.on("husky.auto-complete-list."+this.autoCompleteInstanceName+".initialized",function(){this.sandbox.emit("husky.auto-complete-list."+this.autoCompleteInstanceName+".set-tags",a.tags)}.bind(this)),this.sandbox.on("husky.auto-complete-list."+this.autoCompleteInstanceName+".items-added",function(){this.dfdListenForChange.resolve()}.bind(this))):this.dfdListenForChange.resolve()},initLogoContainer:function(b){b.logo&&b.logo.id&&this.updateLogoContainer(b.logo.id,b.logo.thumbnails[d.logoThumbnailFormat],b.logo.url);var c=function(){var a=this.sandbox.dom.data(d.logoImageId,"mediaId"),c=a?"/admin/api/media/"+a+"?action=new-version":"/admin/api/media?collection="+this.formOptions.accountAvatarCollection;return c=c+"&locale="+encodeURIComponent(this.sandbox.sulu.getDefaultContentLocale()),b.name&&(c=c+"&title="+encodeURIComponent(b.name)),c}.bind(this);this.sandbox.start([{name:"dropzone@husky",options:{el:d.logoDropzoneSelector,maxFilesize:a.get("sulu-media").maxFilesize,instanceName:"account-logo",titleKey:"",descriptionKey:"contact.accounts.logo-dropzone-text",url:c,skin:"overlay",method:"POST",paramName:"fileVersion",showOverlay:!1,maxFiles:1}}]),this.sandbox.dom.on(d.logoDeleteSelector,"click",function(){var a=this.sandbox.dom.data(d.logoImageId,"mediaId");this.sandbox.sulu.showDeleteDialog(function(b){b&&this.sandbox.util.save("/admin/api/media/"+a,"DELETE").done(function(){this.clearLogoContainer(),this.sandbox.emit("sulu.labels.success.show","contact.accounts.logo.saved")}.bind(this))}.bind(this))}.bind(this))},updateLogoContainer:function(a,b,c){var e=this.sandbox.dom.find(d.logoImageId);this.sandbox.dom.data(e,"mediaId",a),this.sandbox.dom.css(e,"background-image","url("+b+")"),this.sandbox.dom.addClass(e.parent(),"no-default"),this.sandbox.dom.attr(d.avatarDownloadSelector,"href",c)},clearLogoContainer:function(){var a=this.sandbox.dom.find(d.logoImageId);a.removeData("mediaId"),this.sandbox.dom.css(a,"background-image",""),this.sandbox.dom.removeClass(a.parent(),"no-default")},saveLogoData:function(a){if(this.sandbox.dom.data(d.logoImageId,"mediaId"))this.sandbox.emit("sulu.labels.success.show","contact.accounts.logo.saved");else if(this.data.id){var c=this.getData();c.logo={id:a.id},b.saveLogo(c).then(function(a){this.sandbox.emit("sulu.tab.data-changed",a)}.bind(this))}},setDefaults:function(a){this.defaultTypes=a},setTypes:function(a){this.fieldTypes=a},fillFields:function(a,b,c){var d,e=-1,f=a.length;for(b>f&&(f=b);++e<f;)d=e+1>b?{}:{permanent:!0},a[e]?a[e].attributes=d:(a.push(c),a[a.length-1].attributes=d);return a},initAccountData:function(){var a=this.data;return this.sandbox.util.foreach(c,function(b){a.hasOwnProperty(b)||(a[b]=[])}),this.fillFields(a.urls,1,{id:null,url:"",urlType:this.defaultTypes.urlType}),this.fillFields(a.emails,1,{id:null,email:"",emailType:this.defaultTypes.emailType}),this.fillFields(a.phones,1,{id:null,phone:"",phoneType:this.defaultTypes.phoneType}),this.fillFields(a.faxes,1,{id:null,fax:"",faxType:this.defaultTypes.faxType}),this.fillFields(a.notes,1,{id:null,value:""}),a},initForm:function(b){var c=a.get("sulucontact.components.autocomplete.default.account");c.el="#company",c.value=b.parent?b.parent:null,c.instanceName="companyAccount"+b.id,this.sandbox.start([{name:"auto-complete@husky",options:c},{name:"input@husky",options:{el:"#vat",instanceName:"vat-input",value:b.uid?b.uid:""}}]),this.numberOfAddresses=b.addresses.length,this.updateAddressesAddIcon(this.numberOfAddresses),this.sandbox.on("sulu.contact-form.initialized",function(){var a=this.sandbox.form.create(d.formSelector);a.initialized.then(function(){this.setFormData(b,!0)}.bind(this))}.bind(this)),this.sandbox.start([{name:"contact-form@sulucontact",options:{el:d.editFormSelector,fieldTypes:this.fieldTypes,defaultTypes:this.defaultTypes}}])},setFormData:function(a,b){this.sandbox.emit("sulu.contact-form.add-collectionfilters",d.formSelector),this.numberOfBankAccounts=a.bankAccounts?a.bankAccounts.length:0,this.updateBankAccountAddIcon(this.numberOfBankAccounts),this.sandbox.form.setData(d.formSelector,a).then(function(){b?this.sandbox.start(d.formSelector):this.sandbox.start(d.formContactFields),this.sandbox.emit("sulu.contact-form.add-required",["email"]),this.sandbox.emit("sulu.contact-form.content-set"),this.dfdFormIsSet.resolve()}.bind(this))},updateAddressesAddIcon:function(a){var b,c=this.$find(d.addressAddId);a&&a>0&&0===c.length?(b=this.sandbox.dom.createElement(e.addAddressesIcon),this.sandbox.dom.after(this.$find("#addresses"),b)):0===a&&c.length>0&&this.sandbox.dom.remove(this.sandbox.dom.closest(c,d.addAddressWrapper))},bindCustomEvents:function(){this.sandbox.on("sulu.contact-form.added.address",function(){this.numberOfAddresses+=1,this.updateAddressesAddIcon(this.numberOfAddresses)},this),this.sandbox.on("sulu.contact-form.removed.address",function(){this.numberOfAddresses-=1,this.updateAddressesAddIcon(this.numberOfAddresses)},this),this.sandbox.on("sulu.tab.save",this.save,this),this.sandbox.on("sulu.contact-form.added.bank-account",function(){this.numberOfBankAccounts+=1,this.updateBankAccountAddIcon(this.numberOfBankAccounts)},this),this.sandbox.on("sulu.contact-form.removed.bank-account",function(){this.numberOfBankAccounts-=1,this.updateBankAccountAddIcon(this.numberOfBankAccounts)},this),this.sandbox.on("husky.dropzone.account-logo.success",function(a,b){this.saveLogoData(b),this.updateLogoContainer(b.id,b.thumbnails[d.logoThumbnailFormat],b.url)},this)},cleanUp:function(){this.sandbox.stop(d.editFormSelector)},copyArrayOfObjects:function(a){var b=[];return this.sandbox.util.foreach(a,function(a){b.push(this.sandbox.util.extend(!0,{},a))}.bind(this)),b},getData:function(){var a=this.sandbox.util.extend(!1,{},this.data,this.sandbox.form.getData(d.formSelector));return a.id||delete a.id,a.logo={id:this.sandbox.dom.data(d.logoImageId,"mediaId")},a.tags=this.sandbox.dom.data(this.$find(d.tagsId),"tags"),a.parent={id:this.sandbox.dom.attr("#company input","data-id")},a},save:function(){if(this.sandbox.form.validate(d.formSelector)){this.sandbox.emit("sulu.tab.saving");var a=this.getData();b.save(a).then(function(a){this.data=a;var b=this.initAccountData();this.setFormData(b),this.sandbox.emit("sulu.tab.saved",a,!0)}.bind(this))}},listenForChange:function(){this.dfdListenForChange.then(function(){this.sandbox.dom.on(d.formSelector,"change keyup",function(){this.sandbox.emit("sulu.tab.dirty")}.bind(this),"select, input, textarea, .trigger-save-button"),this.sandbox.on("sulu.contact-form.changed",function(){this.sandbox.emit("sulu.tab.dirty")}.bind(this))}.bind(this))},updateBankAccountAddIcon:function(a){var b,c=this.$find(d.bankAccountAddId);a&&a>0&&0===c.length?(b=this.sandbox.dom.createElement(e.addBankAccountsIcon),this.sandbox.dom.after(this.$find("#bankAccounts"),b)):0===a&&c.length>0&&this.sandbox.dom.remove(this.sandbox.dom.closest(c,d.addBankAccountsWrapper))}}});