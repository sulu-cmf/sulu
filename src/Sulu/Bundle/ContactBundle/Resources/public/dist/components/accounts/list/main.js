define(["services/sulucontact/account-manager","services/sulucontact/account-router","services/sulucontact/account-delete-dialog"],function(a,b,c){"use strict";var d={datagridInstanceName:"accounts",listViewStorageKey:"accountListView",listPaginationStorageKey:"accountListPagination"},e=function(){this.sandbox.on("sulu.toolbar.delete",function(){this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".items.get-selected",f.bind(this))},this),this.sandbox.on("sulu.contacts.account.deleted",function(a){this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".record.remove",a)},this),this.sandbox.on("sulu.toolbar.add",function(){b.toAdd()},this),this.sandbox.on("husky.datagrid."+d.datagridInstanceName+".number.selections",function(a){var b=a>0?"enable":"disable";this.sandbox.emit("sulu.header.toolbar.item."+b,"deleteSelected",!1)},this),this.sandbox.on("sulu.toolbar.change.table",function(){this.sandbox.sulu.saveUserSetting(d.listViewStorageKey,"table"),this.sandbox.sulu.saveUserSetting(d.listPaginationStorageKey,"dropdown"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".view.change","table"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".pagination.change","dropdown"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".change.page",1),this.sandbox.stickyToolbar.reset(this.$el)}.bind(this)),this.sandbox.on("sulu.toolbar.change.cards",function(){this.sandbox.sulu.saveUserSetting(d.listViewStorageKey,"datagrid/decorators/card-view"),this.sandbox.sulu.saveUserSetting(d.listPaginationStorageKey,"infinite-scroll"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".view.change","datagrid/decorators/card-view"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".pagination.change","infinite-scroll"),this.sandbox.emit("husky.datagrid."+d.datagridInstanceName+".change.page",1),this.sandbox.stickyToolbar.reset(this.$el)}.bind(this))},f=function(b){c.showDialog(b,function(c){a["delete"](b,c)}.bind(this))},g=function(a){b.toEdit(a)};return{stickyToolbar:!0,layout:{content:{width:"max"}},header:{noBack:!0,title:"contact.accounts.title",underline:!1,toolbar:{buttons:{add:{},deleteSelected:{},"export":{options:{urlParameter:{flat:!0},url:"/admin/api/accounts.csv"}}}}},templates:["/admin/contact/template/account/list"],initialize:function(){this.render(),e.call(this)},getListToolbarConfig:function(){return{el:this.$find("#list-toolbar-container"),instanceName:"accounts",template:this.sandbox.sulu.buttons.get({accountDecoratorDropdown:{},settings:{options:{dropdownItems:[{type:"columnOptions"}]}}})}},getDatagridConfig:function(){return{el:this.sandbox.dom.find("#companies-list",this.$el),url:"/admin/api/accounts?flat=true",searchInstanceName:"accounts",storageName:"accounts",searchFields:["name","mainEmail"],resultKey:"accounts",instanceName:d.datagridInstanceName,actionCallback:g.bind(this),view:this.sandbox.sulu.getUserSetting(d.listViewStorageKey)||"datagrid/decorators/card-view",pagination:this.sandbox.sulu.getUserSetting(d.listPaginationStorageKey)||"infinite-scroll",viewOptions:{table:{actionIconColumn:"name",noImgIcon:"fa-home"},"datagrid/decorators/card-view":{imageFormat:"100x100-inset",fields:{picture:"logo",title:["name"]},icons:{picture:"fa-home"}}},paginationOptions:{"infinite-scroll":{reachedBottomMessage:"public.reached-list-end"}}}},render:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/contact/template/account/list")),this.sandbox.sulu.initListToolbarAndList.call(this,"accounts","/admin/api/accounts/fields",this.getListToolbarConfig(),this.getDatagridConfig(),"accounts","#companies-list-info")}}});