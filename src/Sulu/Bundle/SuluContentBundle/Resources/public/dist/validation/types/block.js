define(["type/default"],function(a){"use strict";return function(b,c,d){var e={},f={initializeSub:function(){var a,b,c,d=[];for(this.templates={},a=0,b=this.options.config.length;b>a;a++)c=this.options.config[a],this.templates[c.data]=App.dom.find("#"+c.tpl,this.$el).html(),c.id=c.data,c.name=App.translate(c.title),d.push(c);this.id=this.$el.attr("id"),this.$addButton=$("#"+this.id+"-add"),this.propertyName=App.dom.data(this.$el,"mapperProperty"),this.types=d,this.initSelectComponent(d),this.bindDomEvents(),this.setValue([])},getChildren:function(){return this.$el.children()},getMinOccurs:function(){return this.options.min},getMaxOccurs:function(){return this.options.max},canAdd:function(){var a=this.getChildren().length;return null===this.getMaxOccurs()||a<this.getMaxOccurs()},canRemove:function(){var a=this.getChildren().length;return a>this.getMinOccurs()},initSelectComponent:function(a){App.start([{name:"select@husky",options:{el:this.$addButton,instanceName:this.id,defaultLabel:App.translate("sulu.content.add-type"),fixedLabel:!0,style:"action",icon:"circle-plus",data:a.length>1?a:[],selectCallback:function(a){this.addChild(a,{},!0)}.bind(this),noItemsCallback:function(){this.addChild(this.types[0].data,{},!0)}}}])},bindDomEvents:function(){this.$el.on("click",'*[data-mapper-remove="'+this.propertyName+'"]',this.removeClick.bind(this))},removeClick:function(){var a=$(event.target),b=a.closest("."+this.propertyName+"-element");this.canRemove()&&(b.remove(),$(d.$el).trigger("form-remove",[this.propertyName]),this.checkFullAndEmpty())},validate:function(){return!0},addChild:function(a,b,c,e){var f,g,h,i=App.data.deferred();return("undefined"==typeof e||null===e)&&(e=this.getChildren().length),this.canAdd()?(App.dom.remove(App.dom.find("> *:nth-child("+(e+1)+")",this.$el)),delete b.type,f=$.extend({},{index:e,translate:App.translate,type:a},b),g=_.template(this.templates[a],f,d.options.delimiter),h=$(g),App.dom.insertAt(e,"> *",this.$el,h),this.types.length>1&&App.start([{name:"dropdown@husky",options:{el:"#change"+f.index,trigger:".drop-down-trigger",setParentDropDown:!0,instanceName:"change"+f.index,alignment:"left",valueName:"title",translateLabels:!0,clickCallback:function(a){var b=d.mapper.getData(h);this.addChild(a.data,b,!0,e)}.bind(this),data:this.types}}]),d.initFields(h).then(function(){d.mapper.setData(b,h).then(function(){i.resolve(),c&&$(d.$el).trigger("form-add",[this.propertyName,b])}.bind(this))}.bind(this)),this.checkFullAndEmpty()):i.resolve(),i.promise()},checkFullAndEmpty:function(){this.$addButton.removeClass("empty"),this.$addButton.removeClass("full"),this.$el.removeClass("empty"),this.$el.removeClass("full"),this.canAdd()?this.canRemove()||(this.$addButton.addClass("empty"),this.$el.addClass("empty")):(this.$addButton.addClass("full"),this.$el.addClass("full"))},internalSetValue:function(a){var b,c,d,e,f=App.data.deferred(),g=function(){d--,0===d&&f.resolve()};for(this.form.removeFields(this.$el),App.dom.children(this.$el).remove(),c=a.length<this.getMinOccurs()?this.getMinOccurs():a.length,d=c,b=0;c>b;b++)e=a[b]||{},this.addChild(e.type||this.options.default,e).then(function(){g()});return f.promise()},setValue:function(a){var b=this.internalSetValue(a);return b.then(function(){App.logger.log("resolved block set value")}),b},getValue:function(){var a=[];return App.dom.children(this.$el).each(function(){a.push(d.mapper.getData($(this)))}),a}};return new a(b,e,c,"block",f,d)}});