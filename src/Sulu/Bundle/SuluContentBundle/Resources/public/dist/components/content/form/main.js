define(["app-config"],function(a){"use strict";return{view:!0,layout:{changeNothing:!0},ws:null,wsUrl:"",wsPort:"",previewInitiated:!1,opened:!1,template:"",saved:!0,contentChanged:!1,animateTemplateDropdown:!1,initialize:function(){this.sandbox.emit("husky.toolbar.header.item.enable","template",!1),this.dfdListenForChange=this.sandbox.data.deferred(),this.load()},bindCustomEvents:function(){this.sandbox.on("sulu.preview.set-params",function(a,b){this.wsUrl=a,this.wsPort=b},this),this.sandbox.on("sulu.dropdown.template.item-clicked",function(a){this.animateTemplateDropdown=!0,this.checkRenderTemplate(a)},this),this.sandbox.on("sulu.header.toolbar.save",function(){this.submit()},this)},bindDomEvents:function(){this.startListening=!1,this.getDomElementsForTagName("sulu.rlp",function(a){var b=a.$el.data("element");b&&""!==b.getValue()&&void 0!==b.getValue()&&null!==b.getValue()||(this.startListening=!0)}.bind(this)),this.startListening?this.sandbox.dom.one(this.getDomElementsForTagName("sulu.rlp.part"),"focusout",this.setResourceLocator.bind(this)):this.dfdListenForChange.resolve()},load:function(){this.sandbox.emit("sulu.content.contents.get-data",function(a){this.render(a)}.bind(this))},render:function(a){this.bindCustomEvents(),this.listenForChange(),this.data=a,this.data.template?this.checkRenderTemplate(this.data.template):this.checkRenderTemplate()},checkRenderTemplate:function(a){return"string"==typeof a&&(a={template:a}),a&&this.template===a.template?void this.sandbox.emit("sulu.header.toolbar.item.enable","template",!1):(this.sandbox.emit("sulu.header.toolbar.item.loading","template"),void(""!==this.template&&this.contentChanged?this.showRenderTemplateDialog(a):this.loadFormTemplate(a)))},showRenderTemplateDialog:function(a){this.sandbox.emit("sulu.overlay.show-warning","sulu.overlay.be-careful","content.template.dialog.content",function(){return!1}.bind(this),function(){this.loadFormTemplate(a)}.bind(this))},loadFormTemplate:function(a){var b,c;a&&(this.template=a.template),this.formId="#content-form-container",this.$container=this.sandbox.dom.createElement('<div id="content-form-container"/>'),this.html(this.$container),this.sandbox.form.getObject(this.formId)&&(b=this.data,this.data=this.sandbox.form.getData(this.formId),b.id&&(this.data.id=b.id),this.data=this.sandbox.util.extend({},b,this.data)),this.writeStartMessage(),a&&this.sandbox.emit("sulu.content.preview.change-url",{template:a.template}),c=this.getTemplateUrl(a),require([c],function(a){this.renderFormTemplate(a)}.bind(this))},renderFormTemplate:function(a){var b=this.initData(),c={translate:this.sandbox.translate,content:b,options:this.options},d=this.sandbox.util.extend({},c),e=this.sandbox.util.template(a,d);this.sandbox.dom.html(this.formId,e),this.setStateDropdown(b),this.propertyConfiguration={},this.createForm(b).then(function(){this.bindDomEvents(),this.updatePreviewOnly(),this.changeTemplateDropdownHandler()}.bind(this))},createForm:function(a){var b=this.sandbox.form.create(this.formId),c=this.sandbox.data.deferred();return b.initialized.then(function(){this.createConfiguration(this.formId),this.setFormData(a).then(function(){this.sandbox.start(this.$el,{reset:!0}),this.initSortableBlock(),this.bindFormEvents(),this.options.preview&&(this.initPreview(),this.updatePreview(),this.options.preview=!1),c.resolve()}.bind(this))}.bind(this)),c.promise()},createConfiguration:function(a){var b=this.sandbox.dom.find("*[data-property]",a);this.sandbox.dom.each(b,function(a,b){var c=this.sandbox.dom.data(b,"property");c.$el=this.sandbox.dom.$(b),this.sandbox.dom.data(b,"property",null),this.sandbox.dom.removeAttr(b,"data-property",null),this.sandbox.util.foreach(c.tags,function(a){this.propertyConfiguration[a.name]?(this.propertyConfiguration[a.name].properties[a.priority]?this.propertyConfiguration[a.name].properties[a.priority].push(c):this.propertyConfiguration[a.name].properties[a.priority]=[c],this.propertyConfiguration[a.name].highestPriority<a.priority&&(this.propertyConfiguration[a.name].highestProperty=c,this.propertyConfiguration[a.name].highestPriority=a.priority),this.propertyConfiguration[a.name].lowestPriority>a.priority&&(this.propertyConfiguration[a.name].lowestProperty=c,this.propertyConfiguration[a.name].lowestPriority=a.priority)):(this.propertyConfiguration[a.name]={properties:{},highestProperty:c,highestPriority:a.priority,lowestProperty:c,lowestPriority:a.priority},this.propertyConfiguration[a.name].properties[a.priority]=[c])}.bind(this))}.bind(this))},initSortableBlock:function(){var a,b=this.sandbox.dom.find(".sortable",this.$el);b&&b.length>0&&(this.sandbox.dom.sortable(b,"destroy"),a=this.sandbox.dom.sortable(b,{handle:".move",forcePlaceholderSize:!0}),this.sandbox.dom.unbind(a,"sortupdate"),a.bind("sortupdate",function(a){var b=this.sandbox.form.getData(this.formId),c=this.sandbox.dom.data(a.currentTarget,"mapperProperty");this.updatePreview(c,b[c])}.bind(this)))},bindFormEvents:function(){this.sandbox.dom.on(this.formId,"form-remove",function(a,b){var c=this.sandbox.form.getData(this.formId);this.initSortableBlock(),this.updatePreview(b,c[b]),this.setHeaderBar(!1)}.bind(this)),this.sandbox.dom.on(this.formId,"form-add",function(a,b){this.createConfiguration(a.currentTarget),this.sandbox.start($(a.currentTarget));var c=this.sandbox.form.getData(this.formId);this.initSortableBlock(),this.updatePreview(b,c[b])}.bind(this))},setFormData:function(a){var b=this.sandbox.form.setData(this.formId,a),c="title";return this.getDomElementsForTagName("sulu.node.name",function(a){c=a.name}.bind(this)),!a.id||""!==a[c]&&"undefined"!=typeof a[c]&&null!==a[c]||this.sandbox.util.load("/admin/api/nodes/"+a.id+"?webspace="+this.options.webspace+"&language="+this.options.language+"&complete=false&ghost-content=true").then(function(a){a.type&&this.sandbox.dom.attr("#title","placeholder",a.type.value+": "+a[c])}.bind(this)),"index"===this.options.id&&this.sandbox.dom.remove("#show-in-navigation-container"),this.sandbox.dom.attr("#show-in-navigation","checked",a.navigation),b},getDomElementsForTagName:function(a,b){var c,d=$();if(this.propertyConfiguration.hasOwnProperty(a))for(c in this.propertyConfiguration[a].properties)this.propertyConfiguration[a].properties.hasOwnProperty(c)&&this.sandbox.util.foreach(this.propertyConfiguration[a].properties[c],function(a){$.merge(d,a.$el),b&&b(a)});return d},getTemplateUrl:function(a){var b="text!/admin/content/template/form";return b+=a?"/"+a.template+".html":".html",b+="?webspace="+this.options.webspace+"&language="+this.options.language},setHeaderBar:function(a){this.sandbox.emit("sulu.content.contents.set-header-bar",a),this.saved=a,this.saved&&(this.contentChanged=!1)},setStateDropdown:function(a){this.sandbox.emit("sulu.content.contents.set-state",a)},initData:function(){return this.data},setResourceLocator:function(){if("pending"===this.dfdListenForChange.state()){var a={},b=!0;this.getDomElementsForTagName("sulu.rlp.part",function(c){var d=c.$el.data("element").getValue();""!==d?a[this.getSequence(c.$el)]=d:b=!1}.bind(this)),b?(this.startListening=!0,this.sandbox.emit("sulu.content.contents.get-rl",a,function(a){this.getDomElementsForTagName("sulu.rlp",function(b){var c=b.$el.data("element");(""===c.getValue()||void 0===c.getValue()||null===c.getValue())&&c.setValue(a)}.bind(this)),this.dfdListenForChange.resolve(),this.setHeaderBar(!1),this.contentChanged=!0}.bind(this))):this.sandbox.dom.one(this.getDomElementsForTagName("sulu.rlp.part"),"focusout",this.setResourceLocator.bind(this))}},listenForChange:function(){this.dfdListenForChange.then(function(){this.sandbox.dom.on(this.$el,"keyup change",function(){this.setHeaderBar(!1),this.contentChanged=!0}.bind(this),".trigger-save-button")}.bind(this)),this.sandbox.on("sulu.content.changed",function(){this.setHeaderBar(!1),this.contentChanged=!0}.bind(this))},changeTemplateDropdownHandler:function(){this.template&&this.sandbox.emit("sulu.header.toolbar.item.change","template",this.template),this.sandbox.emit("sulu.header.toolbar.item.enable","template",this.animateTemplateDropdown),this.animateTemplateDropdown=!1},submit:function(){this.sandbox.logger.log("save Model");var a;this.sandbox.form.validate(this.formId)&&(a=this.sandbox.form.getData(this.formId),"index"===this.options.id?a.navigation=!0:this.sandbox.dom.find("#show-in-navigation",this.$el).length&&(a.navigation=this.sandbox.dom.prop("#show-in-navigation","checked")),this.sandbox.logger.log("data",a),this.options.data=this.sandbox.util.extend(!0,{},this.options.data,a),this.sandbox.emit("sulu.content.contents.save",a))},initPreview:function(){this.wsDetection()?this.initWs():this.initAjax(),this.previewInitiated=!0,this.sandbox.on("sulu.preview.update",function(a,b,c){if(this.data.id){var d=this.getSequence(a);null===this.ws&&c||this.updatePreview(d,b)}},this)},wsDetection:function(){var a="MozWebSocket"in window?"MozWebSocket":"WebSocket"in window?"WebSocket":null;return null===a?(this.sandbox.logger.log("Your browser doesn't support Websockets."),!1):(window.MozWebSocket&&(window.WebSocket=window.MozWebSocket),!0)},getSequence:function(a){a=$(a);for(var b,c=this.sandbox.dom.data(a,"mapperProperty"),d=a.parents("*[data-mapper-property]"),e=a.parents("*[data-mapper-property-tpl]")[0];!a.data("element");)a=a.parent();return d.length>0&&(b=this.sandbox.dom.data(d[0],"mapperProperty"),"string"!=typeof b&&(b=this.sandbox.dom.data(d[0],"mapperProperty")[0].data),c=[b,$(e).index(),this.sandbox.dom.data(a,"mapperProperty")]),c},updateEvent:function(a){if(this.data.id&&this.previewInitiated){var b=$(a.currentTarget),c=this.sandbox.dom.data(b,"element");this.updatePreview(this.getSequence(b),c.getValue())}},initAjax:function(){this.sandbox.dom.on(this.formId,"focusout",this.updateEvent.bind(this),".preview-update");var a=this.sandbox.form.getData(this.formId);this.updateAjax(a)},initWs:function(){var a=this.wsUrl+":"+this.wsPort;this.sandbox.logger.log("Connect to url: "+a),this.ws=new WebSocket(a),this.ws.onopen=function(){this.sandbox.logger.log("Connection established!"),this.opened=!0,this.sandbox.dom.on(this.formId,"keyup change",this.updateEvent.bind(this),".preview-update"),this.writeStartMessage()}.bind(this),this.ws.onclose=function(){this.opened||(this.ws=null,this.initAjax())}.bind(this),this.ws.onmessage=function(a){var b=JSON.parse(a.data);this.sandbox.logger.log("Message:",b)}.bind(this),this.ws.onerror=function(a){this.sandbox.logger.warn(a),this.ws=null,this.initAjax()}.bind(this)},writeStartMessage:function(){if(null!==this.ws){var b={command:"start",content:this.data.id,type:"form",user:a.getUser().id,webspaceKey:this.options.webspace,languageCode:this.options.language,templateKey:this.template,params:{}};this.ws.send(JSON.stringify(b))}},updatePreview:function(a,b){if(this.previewInitiated){var c={};a&&b?c[a]=b:c=this.sandbox.form.getData(this.formId),null!==this.ws?this.updateWs(c):this.updateAjax(c)}},updatePreviewOnly:function(){if(this.previewInitiated){var a={};null!==this.ws?this.updateWs(a):this.updateAjax(a)}},updateAjax:function(a){var b="/admin/content/preview/"+this.data.id+"?template="+this.template+"&webspace="+this.options.webspace+"&language="+this.options.language;this.sandbox.util.ajax({url:b,type:"POST",data:{changes:a}})},updateWs:function(b){if(null!==this.ws&&this.ws.readyState===this.ws.OPEN){var c={command:"update",content:this.data.id,type:"form",user:a.getUser().id,webspaceKey:this.options.webspace,languageCode:this.options.language,templateKey:this.template,params:{changes:b}};this.ws.send(JSON.stringify(c))}}}});