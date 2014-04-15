define(["app-config"],function(a){"use strict";var b={headline:"contact.accounts.title"},c=["urls","emails","faxes","phones","notes","addresses"];return{view:!0,templates:["/admin/contact/template/account/form"],initialize:function(){this.options=this.sandbox.util.extend(!0,{},b,this.options),this.form="#contact-form",this.saved=!0,this.accountType=this.getAccountType(),this.setHeadlines(this.accountType),this.render(),this.setHeaderBar(!0),this.listenForChange()},render:function(){var a,b;this.sandbox.once("sulu.contacts.set-defaults",this.setDefaults.bind(this)),this.sandbox.once("sulu.contacts.set-types",this.setTypes.bind(this)),this.html(this.renderTemplate("/admin/contact/template/account/form")),this.titleField=this.$find("#name"),a=this.initContactData(),b=[],this.options.data.id&&b.push({id:this.options.data.id}),this.sandbox.start([{name:"auto-complete@husky",options:{el:"#company",remoteUrl:"/admin/api/accounts?searchFields=id,name&flat=true",getParameter:"search",value:a.parent?a.parent:null,instanceName:"companyAccount"+a.id,valueName:"name",noNewValues:!0,excludes:[{id:a.id,name:a.name}]}}]),this.initForm(a),this.bindDomEvents(),this.bindCustomEvents()},setDefaults:function(a){this.defaultTypes=a},setTypes:function(a){this.fieldTypes=a},getAccountType:function(){var b,c,d=0,e=a.getSection("sulu-contact").accountTypes;return this.options.data.id?(b=this.options.data.type,c="id"):this.options.accountTypeName?(b=this.options.accountTypeName,c="name"):(b=0,c="id"),this.sandbox.util.foreach(e,function(a){return a[c]===b?(d=a,this.options.data.type=a.id,!1):void 0}.bind(this)),d},setHeadlines:function(a){var b=this.sandbox.translate(a.translation),c=this.sandbox.translate(this.options.headline);this.options.data.id&&(b+=" #"+this.options.data.id,c=this.options.data.name),this.sandbox.emit("sulu.header.set-title",c)},fillFields:function(a,b,c){var d,e=-1,f=a.length;for(b>f&&(f=b);++e<f;)d=e+1>b?{}:{permanent:!0},a[e]?a[e].attributes=d:(a.push(c),a[a.length-1].attributes=d);return a},initContactData:function(){var a=this.options.data;return this.sandbox.util.foreach(c,function(b){a.hasOwnProperty(b)||(a[b]=[])}),this.fillFields(a.urls,1,{id:null,url:"",urlType:this.defaultTypes.urlType}),this.fillFields(a.emails,1,{id:null,email:"",emailType:this.defaultTypes.emailType}),this.fillFields(a.phones,1,{id:null,phone:"",phoneType:this.defaultTypes.phoneType}),this.fillFields(a.faxes,1,{id:null,fax:"",faxType:this.defaultTypes.faxType}),this.fillFields(a.notes,1,{id:null,value:""}),a},initForm:function(a){this.sandbox.on("sulu.contact-form.initialized",function(){var b=this.sandbox.form.create(this.form);b.initialized.then(function(){this.setFormData(a)}.bind(this))}.bind(this)),this.sandbox.start([{name:"contact-form@sulucontact",options:{el:"#contact-options-dropdown",fieldTypes:this.fieldTypes}}])},setFormData:function(a){this.sandbox.emit("sulu.contact-form.add-collectionfilters",this.form),this.sandbox.form.setData(this.form,a).then(function(){this.sandbox.start(this.form),this.sandbox.emit("sulu.contact-form.add-required",["email"])}.bind(this))},updateHeadline:function(){this.sandbox.emit("sulu.header.set-title",this.sandbox.dom.val(this.titleField))},bindDomEvents:function(){this.sandbox.dom.keypress(this.form,function(a){13===a.which&&(a.preventDefault(),this.submit())}.bind(this))},bindCustomEvents:function(){this.sandbox.on("sulu.header.toolbar.delete",function(){this.sandbox.emit("sulu.contacts.account.delete",this.options.data.id)},this),this.sandbox.on("sulu.contacts.accounts.saved",function(a){this.options.data=a,this.initContactData(),this.setFormData(a),this.setHeaderBar(!0)},this),this.sandbox.on("sulu.header.toolbar.save",function(){this.submit()},this),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.contacts.accounts.list")},this)},submit:function(){if(this.sandbox.form.validate(this.form)){var a=this.sandbox.form.getData(this.form);""===a.id&&delete a.id,this.updateHeadline(),a.parent={id:this.sandbox.dom.data("#company input","id")},this.sandbox.emit("sulu.contacts.accounts.save",a)}},setHeaderBar:function(a){if(a!==this.saved){var b=this.options.data&&this.options.data.id?"edit":"add";this.sandbox.emit("sulu.header.toolbar.state.change",b,a,!0)}this.saved=a},listenForChange:function(){this.sandbox.dom.on("#contact-form","change",function(){this.setHeaderBar(!1)}.bind(this),"select, input, textarea"),this.sandbox.dom.on("#contact-form","keyup",function(){this.setHeaderBar(!1)}.bind(this),"input, textarea"),this.sandbox.on("sulu.contact-form.changed",function(){this.setHeaderBar(!1)}.bind(this))}}});