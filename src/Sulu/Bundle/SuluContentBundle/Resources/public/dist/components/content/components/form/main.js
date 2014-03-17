define(["app-config"],function(a){"use strict";return{view:!0,ws:null,wsUrl:"",wsPort:"",previewInitiated:!1,opened:!1,template:"",templateChanged:!1,contentChanged:!1,hiddenTemplate:!0,initialize:function(){this.saved=!0,this.highlightSaveButton=this.sandbox.sulu.viewStates.justSaved,delete this.sandbox.sulu.viewStates.justSaved,this.state=null,this.formId="#contacts-form-container",this.render(),this.setTitle(),this.setHeaderBar(!0),this.dfdListenForChange=this.sandbox.data.deferred()},render:function(){this.bindCustomEvents(),this.options.data.template?this.changeTemplate(this.options.data.template):this.changeTemplate()},setStateDropdown:function(a){this.state=a.nodeState||0,this.sandbox.emit("sulu.content.contents.getDropdownForState",this.state,function(a){a.length>0&&this.sandbox.emit("sulu.edit-toolbar.content.items.set","state",a)}.bind(this)),this.sandbox.emit("sulu.content.contents.getStateDropdownItem",this.state,function(a){this.sandbox.emit("sulu.edit-toolbar.content.button.set","state",a)}.bind(this))},setTitle:function(){this.options.id&&this.options.data.title?(this.sandbox.emit("sulu.content.set-title",this.options.data.title),this.setBreadcrumb()):this.sandbox.emit("sulu.content.set-title",this.sandbox.translate("content.contents.title"))},setBreadcrumb:function(){var a=this.options.webspace.replace(/_/g,".");if(this.options.data.breadcrumb)for(var b=0,c=this.options.data.breadcrumb.length;++b<c;)a+=" &#187; "+this.options.data.breadcrumb[b].title;this.sandbox.emit("sulu.content.set-title-addition",a)},createForm:function(a){var b=this.sandbox.form.create(this.formId);b.initialized.then(function(){this.setFormData(a).then(function(){this.sandbox.start(this.$el,{reset:!0}),this.options.preview&&(this.initPreview(),this.options.preview=!1)}.bind(this))}.bind(this))},setFormData:function(a){var b=this.sandbox.form.setData(this.formId,a);return this.sandbox.emit("sulu.edit-toolbar.content.item.change","language",this.options.language),this.sandbox.emit("sulu.edit-toolbar.content.item.show","language"),"index"===this.options.id&&this.sandbox.dom.remove("#show-in-navigation-container"),this.sandbox.dom.attr("#show-in-navigation","checked",a.navigation),b},bindDomEvents:function(){this.options.data.id?this.dfdListenForChange.resolve():this.sandbox.dom.one("#title","focusout",this.setResourceLocator.bind(this))},setResourceLocator:function(){var a=this.sandbox.dom.val("#title"),b="#url";""!==a?(this.sandbox.dom.addClass(b,"is-loading"),this.sandbox.dom.css(b,"background-position","99%"),this.sandbox.emit("sulu.content.contents.getRL",a,function(a){this.sandbox.dom.removeClass(b,"is-loading"),this.sandbox.dom.val(b,a),this.dfdListenForChange.resolve(),this.setHeaderBar(!1),this.contentChanged=!0}.bind(this))):this.sandbox.dom.one("#title","focusout",this.setResourceLocator.bind(this))},bindCustomEvents:function(){this.sandbox.on("sulu.content.contents.saved",function(){this.highlightSaveButton=!0,this.setHeaderBar(!0)},this),this.sandbox.on("sulu.edit-toolbar.save",function(){this.submit()},this),this.sandbox.on("sulu.preview.save",function(){this.submit()},this),this.sandbox.on("sulu.preview.delete",function(){this.sandbox.emit("sulu.content.content.delete",this.options.data.id)},this),this.sandbox.on("sulu.edit-toolbar.delete",function(){this.sandbox.emit("sulu.content.content.delete",this.options.data.id)},this),this.sandbox.on("sulu.edit-toolbar.back",function(){this.sandbox.emit("sulu.content.contents.list")},this),this.sandbox.on("sulu.edit-toolbar.preview.new-window",function(){this.openPreviewWindow()},this),this.sandbox.on("sulu.edit-toolbar.preview.split-screen",function(){this.openSplitScreen()},this),this.sandbox.on("sulu.preview.set-params",function(a,b){this.wsUrl=a,this.wsPort=b},this),this.sandbox.on("sulu.content.contents.default-template",function(a){this.template=a,this.sandbox.emit("sulu.edit-toolbar.content.item.change","template",a),this.hiddenTemplate&&(this.hiddenTemplate=!1,this.sandbox.emit("sulu.edit-toolbar.content.item.show","template",a))},this),this.sandbox.on("sulu.edit-toolbar.dropdown.template.item-clicked",function(a){this.sandbox.emit("sulu.edit-toolbar.content.item.loading","template"),this.templateChanged=!0,this.changeTemplate(a)},this),this.sandbox.on("sulu.edit-toolbar.dropdown.languages.item-clicked",function(a){this.sandbox.emit("sulu.content.contents.load",this.options.id,this.options.webspace,a.localization)},this),this.sandbox.on("sulu.content.contents.state.change",function(){this.sandbox.emit("sulu.edit-toolbar.content.item.loading","state")},this),this.sandbox.on("sulu.content.contents.save",function(){this.sandbox.emit("sulu.edit-toolbar.content.item.loading","save-button")},this),this.sandbox.on("sulu.content.contents.state.changed",function(a){this.state=a,this.sandbox.emit("sulu.content.contents.getDropdownForState",this.state,function(a){this.sandbox.emit("sulu.edit-toolbar.content.items.set","state",a,null)}.bind(this)),this.sandbox.emit("sulu.content.contents.getStateDropdownItem",this.state,function(a){this.sandbox.emit("sulu.edit-toolbar.content.button.set","state",a)}.bind(this)),this.sandbox.emit("sulu.edit-toolbar.content.item.enable","state",!0)}.bind(this)),this.sandbox.on("sulu.content.contents.state.changeFailed",function(){this.sandbox.emit("sulu.content.contents.getStateDropdownItem",this.state,function(a){this.sandbox.emit("sulu.edit-toolbar.content.button.set","state",a)}.bind(this)),this.sandbox.emit("sulu.edit-toolbar.content.item.enable","state",!1)}.bind(this))},initData:function(){return this.options.data},submit:function(){if(this.sandbox.logger.log("save Model"),this.sandbox.form.validate(this.formId)){var a,b=this.sandbox.form.getData(this.formId);a="index"===this.options.id?!0:this.sandbox.dom.prop("#show-in-navigation","checked"),this.sandbox.logger.log("data",b),this.sandbox.emit("sulu.content.contents.save",b,this.template,a)}},changeTemplate:function(a){if("string"==typeof a&&(a={template:a}),a&&this.template===a.template)return void this.sandbox.emit("sulu.edit-toolbar.content.item.enable","template",!1);var b=function(){a&&(this.template=a.template),this.setHeaderBar(!1);var b,c;this.sandbox.form.getObject(this.formId)&&(b=this.options.data,this.options.data=this.sandbox.form.getData(this.formId),b.id&&(this.options.data.id=b.id),this.options.data=this.sandbox.util.extend({},b,this.options.data)),c="text!/admin/content/template/form",c+=a?"/"+a.template+".html":".html",c+="?webspace="+this.options.webspace+"&language="+this.options.language,require([c],function(a){var b={translate:this.sandbox.translate},c=this.sandbox.util.extend({},b),d=this.sandbox.util.template(a,c),e=this.initData();this.sandbox.dom.remove(this.formId+" *"),this.sandbox.dom.html(this.$el,d),this.setStateDropdown(e),this.createForm(e),this.bindDomEvents(),this.listenForChange(),this.updatePreviewOnly(),this.sandbox.emit("sulu.edit-toolbar.content.item.change","template",this.template),this.sandbox.emit("sulu.edit-toolbar.content.item.enable","template",this.templateChanged),this.hiddenTemplate&&(this.hiddenTemplate=!1,this.sandbox.emit("sulu.edit-toolbar.content.item.show","template"))}.bind(this))}.bind(this),c=function(){this.sandbox.emit("sulu.dialog.confirmation.show",{content:{title:this.sandbox.translate("content.template.dialog.title"),content:this.sandbox.translate("content.template.dialog.content")},footer:{buttonCancelText:this.sandbox.translate("content.template.dialog.cancel-button"),buttonSubmitText:this.sandbox.translate("content.template.dialog.submit-button")},callback:{submit:function(){this.sandbox.emit("husky.dialog.hide"),b()}.bind(this),cancel:function(){this.sandbox.emit("husky.dialog.hide")}.bind(this)}},null)}.bind(this);""!==this.template&&this.contentChanged?c():b()},setHeaderBar:function(a){if(a!==this.saved){var b=this.options.data&&this.options.data.id?"edit":"add";this.sandbox.emit("sulu.edit-toolbar.content.state.change",b,a,this.highlightSaveButton),this.sandbox.emit("sulu.preview.state.change",a)}this.saved=a,this.saved&&(this.contentChanged=!1,this.highlightSaveButton=!1)},listenForChange:function(){this.dfdListenForChange.then(function(){this.sandbox.dom.on(this.formId,"keyup",function(){this.setHeaderBar(!1),this.contentChanged=!0}.bind(this),".trigger-save-button"),this.sandbox.dom.on(this.formId,"change",function(){this.setHeaderBar(!1),this.contentChanged=!0}.bind(this),".trigger-save-button"),this.sandbox.on("sulu.content.changed",function(){this.setHeaderBar(!1),this.contentChanged=!0}.bind(this))}.bind(this))},openPreviewWindow:function(){this.options.data.id&&(this.initPreview(),window.open("/admin/content/preview/"+this.options.data.id+"?webspace="+this.options.webspace+"&language="+this.options.language))},openSplitScreen:function(){window.open("/admin/content/split-screen/"+this.options.webspace+"/"+this.options.language+"/"+this.options.data.id)},wsDetection:function(){var a="MozWebSocket"in window?"MozWebSocket":"WebSocket"in window?"WebSocket":null;return null===a?(this.sandbox.logger.log("Your browser doesn't support Websockets."),!1):(window.MozWebSocket&&(window.WebSocket=window.MozWebSocket),!0)},initPreview:function(){this.wsDetection()?this.initWs():this.initAjax(),this.previewInitiated=!0,this.sandbox.on("sulu.preview.update",function(a,b){this.options.data.id&&this.updatePreview(a,b)},this)},updateEvent:function(a){if(this.options.data.id&&this.previewInitiated){for(var b=$(a.currentTarget);!b.data("element");)b=b.parent();this.updatePreview(b.data("mapperProperty"),b.data("element").getValue())}},initAjax:function(){this.sandbox.dom.on(this.formId,"focusout",this.updateEvent.bind(this),".preview-update");var a=this.sandbox.form.getData(this.formId);this.updateAjax(a)},initWs:function(){var b=this.wsUrl+":"+this.wsPort;this.sandbox.logger.log("Connect to url: "+b),this.ws=new WebSocket(b),this.ws.onopen=function(){this.sandbox.logger.log("Connection established!"),this.opened=!0,this.sandbox.dom.on(this.formId,"keyup",this.updateEvent.bind(this),".preview-update");var b={command:"start",content:this.options.data.id,type:"form",user:a.getUser().id,webspace:this.options.webspace,language:this.options.language,params:{}};this.ws.send(JSON.stringify(b))}.bind(this),this.ws.onclose=function(){this.opened||(this.ws=null,this.initAjax())}.bind(this),this.ws.onmessage=function(a){var b=JSON.parse(a.data);"start"===b.command&&b.content===this.options.id&&b.params.other&&this.updatePreview(),this.sandbox.logger.log("Message:",b)}.bind(this),this.ws.onerror=function(a){this.sandbox.logger.warn(a),this.ws=null,this.initAjax()}.bind(this)},updatePreview:function(a,b){if(this.previewInitiated){var c={};a&&b?c[a]=b:c=this.sandbox.form.getData(this.formId),null!==this.ws?this.updateWs(c):this.updateAjax(c)}},updatePreviewOnly:function(){if(this.previewInitiated){var a={};null!==this.ws?this.updateWs(a):this.updateAjax(a)}},updateAjax:function(a){var b="/admin/content/preview/"+this.options.data.id+"?template="+this.template+"&webspace="+this.options.webspace+"&language="+this.options.language;this.sandbox.util.ajax({url:b,type:"POST",data:{changes:a}})},updateWs:function(b){var c={command:"update",content:this.options.data.id,type:"form",user:a.getUser().id,webspaceKey:this.options.webspace,languageCode:this.options.language,params:{changes:b,template:this.template}};this.ws.send(JSON.stringify(c))}}});