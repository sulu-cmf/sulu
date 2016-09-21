define(["underscore","config","services/sulumedia/user-settings-manager","text!./skeleton.html"],function(a,b,c,d){"use strict";var e=[{name:"id",translation:"public.id",disabled:!0,"default":!1,sortable:!0},{name:"thumbnails",translation:"media.media.thumbnails",disabled:!1,"default":!0,sortable:!0,type:"thumbnails"},{name:"title",translation:"public.title",disabled:!1,"default":!1,sortable:!0,type:"title"},{name:"size",translation:"media.media.size",disabled:!1,"default":!0,sortable:!0,type:"bytes"}];return{defaults:{options:{preselected:[],url:"/admin/api/media",singleSelect:!1,removeable:!0,instanceName:null,types:null,removeOnClose:!1,openOnStart:!1,saveCallback:function(a){},removeCallback:function(){}},templates:{skeleton:d,url:["<%= url %>?locale=<%= locale %>","<% if (!!types) {%>&types=<%= types %><% } %>","<% _.each(params, function(value, key) {%>&<%= key %>=<%= value %><% }) %>"].join("")},translations:{title:"sulu-media.selection.overlay.title",save:"sulu-media.selection.overlay.save",remove:"public.remove",uploadInfo:"media-selection.list-toolbar.upload-info",allMedias:"media-selection.overlay.all-medias"}},events:{names:{setItems:{postFix:"set-items",type:"on"},open:{postFix:"open",type:"on"}},namespace:"sulu.media-selection-overlay."},loadedItems:{},initialize:function(){this.initializeDialog(),this.bindCustomEvents()},bindCustomEvents:function(){this.options.removeOnClose&&this.sandbox.on("husky.overlay."+this.options.instanceName+".closed",function(){this.sandbox.stop()}.bind(this)),this.sandbox.on("husky.dropzone."+this.options.instanceName+".files-added",function(a){return this.sandbox.emit("sulu.labels.success.show","labels.success.media-upload-desc","labels.success"),this.options.singleSelect?(this.setItems([a[0]]),this.save(),this.sandbox.emit("husky.overlay."+this.options.instanceName+".close")):void this.addFilesToDatagrid.call(this,a)}.bind(this)),this.sandbox.on("sulu.toolbar."+this.options.instanceName+".add",function(){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".open-data-source")}.bind(this)),this.sandbox.on("husky.overlay.dropzone-"+this.options.instanceName+".opened",function(){this.$el.find(".media-selection-overlay").addClass("dropzone-overlay-opened")}.bind(this)),this.sandbox.on("husky.overlay.dropzone-"+this.options.instanceName+".closed",function(){this.$el.find(".media-selection-overlay").removeClass("dropzone-overlay-opened")}.bind(this)),this.sandbox.on("sulu.toolbar.change.table",function(){c.setMediaListView("table"),c.setMediaListPagination("dropdown"),this.sandbox.emit("husky.datagrid."+this.options.instanceName+".change",1,c.getDropdownPageSize(),"table",[],"dropdown")}.bind(this)),this.sandbox.on("sulu.toolbar.change.masonry",function(){c.setMediaListView("datagrid/decorators/masonry-view"),c.setMediaListPagination("infinite-scroll"),this.sandbox.emit("husky.datagrid."+this.options.instanceName+".change",1,c.getInfinityPageSize(),"datagrid/decorators/masonry-view",null,"infinite-scroll")}.bind(this)),this.events.setItems(this.setItems.bind(this)),this.events.open(function(){this.sandbox.emit("husky.overlay."+this.options.instanceName+".open")}.bind(this)),this.sandbox.on("husky.datagrid."+this.options.instanceName+".item.select",function(a,b){this.addItem(b)}.bind(this)),this.sandbox.on("husky.datagrid."+this.options.instanceName+".item.deselect",function(a){this.removeItem(a)}.bind(this)),this.sandbox.on("husky.datagrid."+this.options.instanceName+".loaded",function(b){a.each(b._embedded.media,function(a){this.loadedItems[a.id]=a}.bind(this))}.bind(this))},save:function(){this.options.saveCallback(this.getData())},getData:function(){return a.map(this.items,function(a){return this.loadedItems&&this.loadedItems[a.id]?this.loadedItems[a.id]:a}.bind(this))},setItems:function(b){this.items=b;var c=a.map(this.items,function(a){return parseInt(a.id)});this.sandbox.emit("husky.datagrid."+this.options.instanceName+".selected.update",c)},addItem:function(a){return this.has(a.id)?!1:(this.items.push(a),!0)},removeItem:function(b){this.items=a.filter(this.items,function(a){return a.id!==b})},has:function(b){return!!a.filter(this.items,function(a){return a.id===b}).length},getUrl:function(a){return a||(a={}),this.templates.url({url:this.options.url,locale:this.options.locale,types:this.options.types,params:a})},changeUploadCollection:function(a){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".change-url",this.getUrl({collection:a}))},addFilesToDatagrid:function(a){for(var b=-1,c=a.length;++b<c;)a[b].selected=!0;this.sandbox.emit("husky.datagrid."+this.options.instanceName+".records.add",a)},initializeDialog:function(){var a=this.sandbox.dom.createElement('<div class="overlay-container"/>');this.sandbox.dom.append(this.$el,a);var b=[{type:"cancel",align:"left"}];this.options.removeable&&b.push({text:this.translations.remove,align:"center",classes:"just-text",callback:function(){this.options.removeCallback(),this.sandbox.emit("husky.overlay."+this.options.instanceName+".close")}.bind(this)}),this.options.singleSelect||b.push({type:"ok",text:this.translations.save,align:"right"}),this.sandbox.start([{name:"overlay@husky",options:{openOnStart:this.options.openOnStart,removeOnClose:this.options.removeOnClose,el:a,container:this.$el,contentSpacing:!1,cssClass:"media-selection-overlay",instanceName:this.options.instanceName,slides:[{title:this.translations.title,data:this.templates.skeleton({title:this.translations.allMedias}),buttons:b,okCallback:function(){this.save()}.bind(this)}]}}]).then(function(){return this.setItems(this.options.preselected),this.options.openOnStart?this.initializeFormComponents():void this.sandbox.once("husky.overlay."+this.options.instanceName+".opened",function(){this.initializeFormComponents()}.bind(this))}.bind(this))},initializeFormComponents:function(){this.sandbox.start([{name:"dropzone@husky",options:{el:this.$el.find(".dropzone-container"),maxFilesize:b.get("sulu-media").maxFilesize,url:this.getUrl(),method:"POST",paramName:"fileVersion",instanceName:this.options.instanceName,overlayContainer:this.$find(".overlay-content"),dropzoneEnabled:!1,cancelUploadOnOverlayClose:!0}}]),this.sandbox.sulu.initListToolbarAndList.call(this,"mediaOverlay",e,{el:this.$el.find(".list-toolbar-container"),instanceName:this.options.instanceName,template:this.sandbox.sulu.buttons.get({add:{options:{id:"add",title:this.translations.uploadInfo,hidden:!0,callback:function(){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".open-data-source")}.bind(this)}},mediaDecoratorDropdown:{options:{id:"change",dropdownOptions:{markSelected:!0}}}})},{el:this.$el.find(".list-datagrid-container"),url:this.getUrl({orderBy:"media.created",orderSort:"desc"}),view:c.getMediaListView(),pagination:c.getMediaListPagination(),resultKey:"media",instanceName:this.options.instanceName,searchFields:["name","title","description"],viewSpacingBottom:180,selectedCounter:!1,preselected:a.map(this.items,function(a){return parseInt(a.id)}),actionCallback:this.options.singleSelect?function(a,b){this.setItems([b]),this.save(),this.sandbox.emit("husky.overlay."+this.options.instanceName+".close")}.bind(this):null,viewOptions:{table:{actionIcon:"check",actionIconColumn:this.options.singleSelect?"title":null,selectItem:this.options.singleSelect?!1:{type:"checkbox",inFirstCell:!1},badges:[{column:"title",callback:function(a,b){return a.locale!==this.options.locale?(b.title=a.locale,b):void 0}.bind(this)}]},"datagrid/decorators/masonry-view":{selectable:!this.options.singleSelect,selectOnAction:!this.options.singleSelect,unselectOnBackgroundClick:!1,locale:this.options.locale,actionIcons:["fa-check-circle-o"],badges:[{column:"title",callback:function(a,b){return a.locale!==this.options.locale?(b.title=a.locale,b):void 0}.bind(this)}]}},paginationOptions:{"infinite-scroll":{reachedBottomMessage:"public.reached-list-end",scrollContainer:".list-container",scrollOffset:500}}})}}});