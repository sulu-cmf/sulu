define([],function(){"use strict";var a={eventNamespace:"sulu.internal-links",resultKey:"nodes",idKey:"uuid",locale:null,webspace:null,hideConfigButton:!0,hidePositionElement:!0,dataAttribute:"internal-links",actionIcon:"fa-link",dataDefault:[],navigateEvent:"sulu.router.navigate",publishedStateName:"publishedState",publishedName:"published",translations:{noContentSelected:"internal-links.nolinks-selected",addLinks:"internal-links.add",visible:"public.visible",of:"public.of",unpublished:"public.unpublished",publishedWithDraft:"public.published-with-draft"}},b={data:function(a){return['<div id="',a.ids.columnNavigation,'"/>'].join("")},contentItem:function(a,b,c,d,e){return['<a href="#" data-id="',a,'" class="link">','    <span class="icons">'+d+"</span>",'    <span class="value" title="',b,'">',"function"==typeof e?e(b,48):b,"</span>",'    <span class="description" title="',c,'">',"function"==typeof e?e(c,55):b,"</span>","</a>"].join("")},icons:{draft:function(a){return'<span class="draft-icon" title="'+a+'"/>'},published:function(a){return['<span class="published-icon" title="'+a+'"/>'].join("")}}},c=function(a){return"#"+this.options.ids[a]},d=function(){this.sandbox.on("sulu.internal-links."+this.options.instanceName+".add-button-clicked",h.bind(this)),this.sandbox.on("husky.overlay.internal-links."+this.options.instanceName+".add.initialized",f.bind(this)),this.sandbox.dom.on(this.$el,"click",function(a){var b=this.sandbox.dom.data(a.currentTarget,"id");return this.sandbox.emit(this.options.navigateEvent,"content/contents/"+this.options.webspace+"/"+this.options.locale+"/edit:"+b+"/content"),!1}.bind(this),"a.link")},e=function(a){var b=this.getData();-1===b.indexOf(a.id)&&(a.uuid=a.id,b.push(a.id),this.setData(b,!1),this.addItem(a))},f=function(){var a=this.getData();this.sandbox.start([{name:"column-navigation@husky",options:{el:c.call(this,"columnNavigation"),url:g.call(this),linkedName:"linked",typeName:"type",hasSubName:"hasChildren",instanceName:this.options.instanceName,resultKey:this.options.resultKey,showOptions:!1,responsive:!1,skin:"fixed-height-small",markable:!0,sortable:!1,premarkedIds:a}}])},g=function(){var a="/admin/api/nodes",b=["language="+this.options.locale,"fields=title,order,published","webspace-nodes=all"];return this.options.webspace&&b.push("webspace="+this.options.webspace),a+"?"+b.join("&")},h=function(){var a=this.sandbox.dom.createElement("<div/>");this.sandbox.dom.append(this.$el,a),this.sandbox.start([{name:"overlay@husky",options:{cssClass:"internal-links-overlay",el:a,container:this.$el,openOnStart:!0,instanceName:"internal-links."+this.options.instanceName+".add",skin:"responsive-width",slides:[{title:this.sandbox.translate(this.options.translations.addLinks),cssClass:"internal-links-overlay-add",data:b.data(this.options),contentSpacing:!1,okCallback:function(){this.overlayOkCallback(),this.sandbox.stop(c.call(this,"columnNavigation"))}.bind(this),cancelCallback:function(){this.sandbox.stop(c.call(this,"columnNavigation"))}.bind(this)}]}}])};return{type:"itembox",initialize:function(){this.options=this.sandbox.util.extend(!0,{},a,this.options),this.options.ids={container:"internal-links-"+this.options.instanceName+"-container",addButton:"internal-links-"+this.options.instanceName+"-add",configButton:"internal-links-"+this.options.instanceName+"-config",displayOption:"internal-links-"+this.options.instanceName+"-display-option",content:"internal-links-"+this.options.instanceName+"-content",chooseTab:"internal-links-"+this.options.instanceName+"-choose-tab",columnNavigation:"internal-links-"+this.options.instanceName+"-column-navigation"},this.render(),d.call(this)},getUrl:function(a){var b=-1===this.options.url.indexOf("?")?"?":"&";return[this.options.url,b,this.options.idsParameter,"=",(a||[]).join(",")].join("")},overlayOkCallback:function(){this.sandbox.emit("husky.column-navigation."+this.options.instanceName+".get-marked",function(a){var b=this.sandbox.util.deepCopy(this.getData());$.each(a,function(a,c){$.inArray(a,b)<0&&e.call(this,c)}.bind(this)),b.forEach(function(b){b in a||this.removeHandler(b)}.bind(this))}.bind(this))},getItemContent:function(a){return b.contentItem(a[this.options.idKey],a.title,a.url,this.getItemIcons(a),this.sandbox.util.cropMiddle)},getItemIcons:function(a){if(void 0===a[this.options.publishedStateName])return"";var c="",d=this.sandbox.translate(this.options.translations.unpublished);return!a[this.options.publishedStateName]&&a[this.options.publishedName]&&(d=this.sandbox.translate(this.options.translations.publishedWithDraft),c+=b.icons.published(d)),a[this.options.publishedStateName]||(c+=b.icons.draft(d)),c},sortHandler:function(a){this.setData(a,!1)},removeHandler:function(a){for(var b=this.getData(),c=-1,d=b.length;++c<d;)if(a===b[c]){b.splice(c,1);break}this.setData(b)}}});