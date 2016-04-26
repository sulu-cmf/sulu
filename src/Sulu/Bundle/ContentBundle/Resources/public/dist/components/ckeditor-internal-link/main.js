define(["underscore","text!./form.html"],function(a,b){"use strict";var c="#internal-link-form";return{defaults:{options:{link:{},saveCallback:function(a){}},templates:{form:b,contentDatasource:'<div id="href-select" class="data-source-content"/>'},translations:{save:"public.save",back:"public.previous",altTitle:"content.ckeditor.internal-link.alt-title",href:"content.ckeditor.internal-link.href",target:"content.ckeditor.internal-link.target",targetBlank:"content.ckeditor.internal-link.target-blank",targetSelf:"content.ckeditor.internal-link.target-self",internalLink:"content.ckeditor.internal-link"}},initialize:function(){this.initializeDialog()},bindDomEvents:function(){this.sandbox.dom.on(this.$el,"click",function(){return this.sandbox.emit("husky.overlay.internal-link.slide-to",1),!1}.bind(this),".internal-link-href, #internal-link-href-button"),this.sandbox.dom.on(this.$el,"click",function(){return this.href=null,$("#internal-link-href-button-clear").hide(),$("#internal-link-href-value").val(""),!1}.bind(this),"#internal-link-href-button-clear")},save:function(){this.sandbox.form.validate(c)&&this.options.saveCallback(this.getData())},getData:function(){var b=this.sandbox.form.getData(c,b);return a.defaults(b,{href:this.href,title:this.options.link.title?this.options.link.title:this.hrefTitle})},setData:function(a){return this.sandbox.form.setData(c,a)},setHref:function(a){this.href=a.id,this.hrefTitle=a.title;var b=$("#internal-link-href-value");b.val(a.title),$("#internal-link-href-button-clear").show()},initializeDialog:function(){var a=this.sandbox.dom.createElement('<div class="overlay-container"/>');this.sandbox.dom.append(this.$el,a),this.sandbox.start([{name:"overlay@husky",options:{openOnStart:!0,removeOnClose:!0,el:a,container:this.$el,skin:"wide",instanceName:"internal-link",slides:[{title:this.translations.internalLink,data:this.templates.form({translations:this.translations}),buttons:[{type:"cancel",align:"left"},{type:"ok",text:this.translations.save,align:"right"}],okCallback:this.save.bind(this)},{title:this.translations.internalLink,data:this.templates.contentDatasource(),cssClass:"data-source-slide",buttons:[{type:"cancel",text:this.translations.back,align:"left"}],cancelCallback:function(){return this.sandbox.emit("husky.overlay.internal-link.slide-to",0),!1}.bind(this)}]}}]).then(function(){this.sandbox.form.create(c).initialized.then(function(){this.setData(this.options.link).then(this.initializeFormComponents.bind(this)),this.bindDomEvents()}.bind(this))}.bind(this))},initializeFormComponents:function(){this.sandbox.start([{name:"loader@husky",options:{el:this.$find(".loader")}},{name:"content-datasource@sulucontent",options:{el:"#href-select",selected:this.options.link.href,webspace:this.options.webspace,locale:this.options.locale,selectedUrl:"/admin/api/nodes/{datasource}?tree=true&webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=all",rootUrl:"/admin/api/nodes?webspace={webspace}&language={locale}&fields=title,order&webspace-nodes=all",resultKey:"nodes",instanceName:"internal-link",instanceNamePrefix:"",selectCallback:function(a,b,c){var d=$("#internal-link-href-value");d.val(c),$("#internal-link-href-button-clear").show(),this.href=a,this.hrefTitle=c,this.sandbox.emit("husky.overlay.internal-link.slide-to",0)}.bind(this)}}]).then(function(){return this.options.link.href?void this.sandbox.once("husky.column-navigation.internal-link.loaded",function(){this.sandbox.emit("husky.column-navigation.internal-link.get-breadcrumb",function(a){return 0===a.length?void this.showHrefInput():(this.setHref(a[a.length-1]),void this.showHrefInput())}.bind(this))}.bind(this)):void this.showHrefInput()}.bind(this))},showHrefInput:function(){this.$find(".loader").hide(),this.$find(".href-container").show()}}});