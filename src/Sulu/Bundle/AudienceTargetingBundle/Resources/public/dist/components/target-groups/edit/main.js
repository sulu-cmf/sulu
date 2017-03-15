define(["jquery","services/suluaudiencetargeting/target-group-manager","services/suluaudiencetargeting/target-group-router"],function(a,b,c){"use strict";return{defaults:{translations:{description:"public.description"}},header:function(){var a={save:{parent:"saveWithOptions"}};return this.options.id&&(a.edit={options:{dropdownItems:{"delete":{options:{callback:this.showDeleteConfirmation.bind(this)}}}}}),{tabs:{url:"/admin/content-navigations?alias=target-group",options:{data:function(){return this.sandbox.util.extend(!1,{},this.data)}.bind(this)},componentOptions:{values:this.data}},toolbar:{buttons:a}}},loadComponentData:function(){var d=a.Deferred();return this.options.id?(b.load(this.options.id).done(function(a){d.resolve(a)}).fail(function(){this.sandbox.emit("sulu.labels.error.show","sulu_audience_targeting.target-group-not-found"),c.toList()}.bind(this)),d):(d.resolve({title:"",description:"",priority:null,webspaces:[],active:!1}),d)},initialize:function(){this.bindCustomEvents()},bindCustomEvents:function(){this.sandbox.on("sulu.header.back",this.toList.bind(this)),this.sandbox.on("sulu.tab.dirty",this.enableSave.bind(this)),this.sandbox.on("sulu.toolbar.save",this.save.bind(this)),this.sandbox.on("sulu.tab.data-changed",this.setData.bind(this))},toList:function(){c.toList()},showDeleteConfirmation:function(){this.sandbox.sulu.showDeleteDialog(function(a){a&&this["delete"]()}.bind(this))},"delete":function(){this.sandbox.emit("sulu.header.toolbar.item.loading","edit"),b["delete"](this.data.id).done(function(){this.sandbox.emit("sulu.header.toolbar.item.enable","edit",!1),c.toList()}.bind(this))},save:function(a){this.loadingSave(),this.saveTab().then(function(b){this.afterSave(a,b)}.bind(this))},setData:function(a){this.data=a},saveTab:function(){var b=a.Deferred();return this.sandbox.once("sulu.tab.saved",function(a){this.setData(a),b.resolve(a)}.bind(this)),this.sandbox.emit("sulu.tab.save"),b},enableSave:function(){this.sandbox.emit("sulu.header.toolbar.item.enable","save",!1)},loadingSave:function(){this.sandbox.emit("sulu.header.toolbar.item.loading","save")},afterSave:function(a,b){this.sandbox.emit("sulu.header.toolbar.item.disable","save",!0),this.sandbox.emit("sulu.header.saved",b),console.error(a),"back"===a?c.toList():"new"===a?c.toAdd():this.options.id||c.toEdit(b.id)}}});