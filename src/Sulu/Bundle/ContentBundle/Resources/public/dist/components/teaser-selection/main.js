define(["underscore"],function(a){"use strict";var b={options:{eventNamespace:"sulu.teaser-selection",resultKey:"teasers",dataAttribute:"teaser-selection",dataDefault:{},hidePositionElement:!0,hideConfigButton:!0,webspaceKey:null,locale:null,navigateEvent:"sulu.router.navigate",idKey:"teaserId",types:{},presentAs:[],translations:{noContentSelected:"sulu-content.teaser.no-teaser",add:"sulu-content.teaser.add-teaser"}},templates:{url:'/admin/api/teasers?ids=<%= ids.join(",") %>',contentItem:'<% if (!!media) { %><span class="image"><img src="<%= media %>"/></span><% } %><span class="value"><%= title %></span>',presentAsButton:'<span class="fa-eye present-as teaser-selection icon right border"><span class="selected-text"></span><span class="dropdown-toggle"></span></span>'}},c=function(){var b=$("<div/>");this.$addButton.parent().append(b),this.$addButton.append('<span class="dropdown-toggle teaser-selection"/>'),this.sandbox.start([{name:"dropdown@husky",options:{el:b,data:a.map(this.options.types,function(b,c){return a.extend({id:c,name:c},b)}),valueName:"title",trigger:this.$addButton,triggerOutside:!0,clickCallback:e.bind(this)}}])},d=function(){var b=$(this.templates.presentAsButton()),c=b.find(".selected-text"),d=$("<div/>"),e=this.getData().presentAs||"";b.insertAfter(this.$addButton),this.$addButton.parent().append(d),a.each(this.options.presentAs,function(a){return a.id===e?(c.text(a.name),!1):void 0}),this.sandbox.start([{name:"dropdown@husky",options:{el:d,instanceName:this.options.instanceName,data:this.options.presentAs,alignment:"right",trigger:b,triggerOutside:!0,clickCallback:function(b){c.text(b.name),this.setData(a.extend(this.getData(),{presentAs:b.id}))}.bind(this)}}])},e=function(b){var c=$('<div class="teaser-selection"/>'),d=$("<div/>"),e=this.getData().items||[];this.$el.append(c),this.sandbox.start([{name:"overlay@husky",options:{el:c,instanceName:this.options.instanceName,openOnStart:!0,removeOnClose:!0,cssClass:"type-"+b.name,slides:[{title:this.sandbox.translate(b.title),data:d,okCallback:function(){var a=this.getData();a.items=e,this.setData(a)}.bind(this)}]}}]),this.sandbox.once("husky.overlay."+this.options.instanceName+".initialized",function(){this.sandbox.start([{name:b.component,options:a.extend({el:d,locale:this.options.locale,webspaceKey:this.options.webspaceKey,instanceName:this.options.instanceName,type:b.name,data:a.fsrc/Sulu/Component/Content/SimpleContentType.phpilter(e,function(a){return a.type===b.name}),selectCallback:function(a){e.push(a)},deselectCallback:function(b){e=a.without(e,a.findWhere(e,b))}},b.componentOptions)}])}.bind(this))};return{type:"itembox",defaults:b,initialize:function(){this.render(),c.call(this),d.call(this)},getUrl:function(b){var c=a.map(b.items||[],function(a){return a.type+";"+a.id});return this.templates.url({ids:c})},getItemContent:function(a){return this.templates.contentItem(a)},sortHandler:function(b){var c=this.getData();c.items=a.map(b,function(a){var b=a.split(";");return{type:b[0],id:b[1]}}),this.setData(c,!1)},removeHandler:function(a){for(var b=this.getData(),c=b.items||[],d=a.split(";"),e=-1,f=c.length;++e<f;)if(d[0]===c[e].type&&d[1]===c[e].id){c.splice(e,1);break}b.items=c,this.setData(b,!1)}}});