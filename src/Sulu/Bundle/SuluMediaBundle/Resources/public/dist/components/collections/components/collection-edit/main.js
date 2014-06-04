define(function(){"use strict";var a={activeTab:null,data:{},instanceName:"collection"},b={FILES:"files",SETTINGS:"settings"},c={dropzoneSelector:".dropzone-container",toolbarSelector:".list-toolbar-container",datagridSelector:".datagrid-container",settingsFormId:"collection-settings"};return{view:!0,fullSize:{width:!0,keepPaddings:!0},templates:["/admin/media/template/collection/files","/admin/media/template/collection/settings"],initialize:function(){this.options=this.sandbox.util.extend(!0,{},a,this.options),this.saved=!0,this.bindCustomEvents(),this.render()},bindCustomEvents:function(){this.sandbox.on("sulu.list-toolbar.change.table",function(){this.sandbox.emit("husky.datagrid.view.change","table")}.bind(this)),this.sandbox.on("sulu.list-toolbar.change.thumbnail-small",function(){this.sandbox.emit("husky.datagrid.view.change","thumbnail",{large:!1})}.bind(this)),this.sandbox.on("sulu.list-toolbar.change.thumbnail-large",function(){this.sandbox.emit("husky.datagrid.view.change","thumbnail",{large:!0})}.bind(this)),this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.media.collections.list")}.bind(this)),this.sandbox.on("husky.dropzone."+this.options.instanceName+".files-added",function(a){this.addFilesToDatagrid(a)}.bind(this)),this.sandbox.on("sulu.list-toolbar.add",function(){this.sandbox.emit("husky.dropzone."+this.options.instanceName+".open-data-source")}.bind(this)),this.sandbox.on("husky.datagrid.item.click",function(a){this.sandbox.emit("sulu.media.collections.edit-media",a)}.bind(this)),this.sandbox.on("sulu.media.collections.media-saved",function(){this.sandbox.emit("husky.datagrid.update")}.bind(this)),this.sandbox.on("sulu.list-toolbar.delete",this.deleteMedia.bind(this)),this.sandbox.on("sulu.header.toolbar.save",this.saveSettings.bind(this)),this.sandbox.on("sulu.header.toolbar.delete",this.deleteCollection.bind(this))},deleteMedia:function(){this.sandbox.emit("husky.datagrid.items.get-selected",function(a){this.sandbox.emit("sulu.media.collections.delete-media",a,function(a){this.sandbox.emit("husky.datagrid.record.remove",a)}.bind(this))}.bind(this))},deleteCollection:function(){this.sandbox.emit("sulu.media.collections.delete-collection",this.options.data.id,function(){this.sandbox.sulu.unlockDeleteSuccessLabel(),this.sandbox.emit("sulu.media.collections.collection-list")}.bind(this))},render:function(){this.setHeaderInfos(),this.options.activeTab===b.FILES?this.renderFiles():this.options.activeTab===b.SETTINGS&&this.renderSettings()},renderFiles:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/media/template/collection/files")),this.startDropzone(),this.startDatagrid()},renderSettings:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/media/template/collection/settings")),this.options.data.color=this.options.data.style.color,this.sandbox.start("#"+c.settingsFormId),this.sandbox.form.create("#"+c.settingsFormId),this.sandbox.form.setData("#"+c.settingsFormId,this.options.data).then(function(){this.startSettingsToolbar(),this.bindSettingsDomEvents()}.bind(this))},bindSettingsDomEvents:function(){this.sandbox.dom.on("#"+c.settingsFormId,"change",function(){this.saved===!0&&(this.sandbox.emit("sulu.header.toolbar.state.change","edit",!1),this.saved=!1)}.bind(this))},startSettingsToolbar:function(){this.sandbox.emit("sulu.header.set-toolbar",{template:"default"})},setHeaderInfos:function(){this.sandbox.emit("sulu.header.set-title",this.options.data.title),this.sandbox.emit("sulu.header.set-breadcrumb",[{title:"navigation.media"},{title:"media.collections.title",event:"sulu.media.collections.list"},{title:this.options.data.title}]),this.sandbox.emit("sulu.header.set-title-color",this.options.data.style.color)},startDropzone:function(){this.sandbox.start([{name:"dropzone@husky",options:{el:this.$find(c.dropzoneSelector),url:"/admin/api/media?collection%5Bid%5D="+this.options.data.id,method:"POST",paramName:"fileVersion",instanceName:this.options.instanceName}}])},saveSettings:function(){if(this.sandbox.form.validate("#"+c.settingsFormId)){var a=this.sandbox.form.getData("#"+c.settingsFormId);a.style={color:a.color},this.options.data=this.sandbox.util.extend(!0,{},this.options.data,a),this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.sandbox.once("sulu.media.collections.collection-changed",this.savedCallback.bind(this)),this.sandbox.emit("sulu.media.collections.save-collection",this.options.data)}},savedCallback:function(){this.setHeaderInfos(),this.sandbox.emit("sulu.header.toolbar.state.change","edit",!0,!0),this.saved=!0,this.sandbox.emit("sulu.labels.success.show","labels.success.collection-save-desc","labels.success")},startDatagrid:function(){this.sandbox.sulu.initListToolbarAndList.call(this,"mediaFields","/admin/api/media/fields",{el:this.$find(c.toolbarSelector),instanceName:this.options.instanceName,parentTemplate:"default",template:"changeable",inHeader:!0},{el:this.$find(c.datagridSelector),url:"/admin/api/media?collection="+this.options.data.id,view:"thumbnail",pagination:!1,viewOptions:{table:{fullWidth:!1}}})},addFilesToDatagrid:function(a){for(var b=-1,c=a.length;++b<c;)a[b].selected=!0;this.sandbox.emit("husky.datagrid.records.add",a)}}});