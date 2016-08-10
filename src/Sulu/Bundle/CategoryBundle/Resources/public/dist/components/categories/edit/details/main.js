define(["jquery","services/sulucategory/category-manager","text!./form.html"],function(a,b,c){"use strict";var d={templates:{form:c,url:"/admin/api/categories<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>",keywordsUrl:'/admin/api/categories/<%= category %>/keywords<% if (typeof id !== "undefined") { %>/<%= id %><% } %><% if (typeof postfix !== "undefined") { %><%= postfix %><% } %>?locale=<%= locale %><% if (typeof ids !== "undefined") { %>&ids=<%= ids.join(",") %><% } %><% if (typeof force !== "undefined") { %>&force=<%= force %><% } %>'},translations:{name:"public.name",key:"public.key",yes:"public.yes",no:"public.no",categoryKey:"sulu.category.category-key",keywords:"sulu.category.keywords",keywordDeleteLabel:"labels.success.delete-desc",keywordDeleteMessage:"labels.success.delete-desc",conflictTitle:"sulu.category.keyword_conflict.title",conflictMessage:"sulu.category.keyword_conflict.message",conflictOverwrite:"sulu.category.keyword_conflict.overwrite",conflictDetach:"sulu.category.keyword_conflict.detach",mergeTitle:"sulu.category.keyword_merge.title",mergeMessage:"sulu.category.keyword_merge.message",categoryName:"sulu.category.category-name"}};return{defaults:d,type:"form-tab",tabInitialize:function(){this.sandbox.on("sulu.toolbar.add",function(){this.sandbox.emit("husky.datagrid.record.add",{id:"",keyword:"",locale:this.options.locale})}.bind(this)),this.sandbox.on("husky.datagrid.data.save.failed",function(a,b,c,d){this.handleFail(a,d)}.bind(this)),this.sandbox.emit("sulu.tab.initialize",this.name)},rendered:function(){this.data.id&&this.startKeywordList(),this.sandbox.emit("sulu.tab.rendered",this.name)},parseData:function(a){this.data=a,this.data.id&&this.data.defaultLocale===this.data.locale&&this.data.locale!==this.options.locale&&(this.fallbackData={locale:this.options.locale,name:this.data.name},this.data.name=null),this.data.locale=this.options.locale},save:function(a){b.save(a,this.options.locale).then(this.saved.bind(this))},getTemplate:function(){var a=this.translations.categoryName;return this.fallbackData&&(a=this.fallbackData.locale.toUpperCase()+": "+this.fallbackData.name),this.templates.form({placeholder:a,translations:this.translations,keywords:!!this.data.id})},getFormId:function(){return"#category-form"},startKeywordList:function(){this.sandbox.sulu.initListToolbarAndList.call(this,"keywords",this.templates.keywordsUrl({category:this.data.id,postfix:"/fields",locale:this.options.locale}),{el:this.$find("#keywords-list-toolbar"),template:this.sandbox.sulu.buttons.get({add:{options:{position:0}},deleteSelected:{options:{position:1,callback:function(){this.deleteKeywords()}.bind(this)}}}),parentTemplate:"default",listener:"default"},{el:this.$find("#keywords-list"),url:this.templates.keywordsUrl({category:this.data.id,locale:this.options.locale}),resultKey:"keywords",searchFields:["keyword"],saveParams:{locale:this.options.locale},contentFilters:{categoryTranslationCount:function(a){return a>1?this.translations.yes:this.translations.no}.bind(this)},viewOptions:{table:{editable:!0,validation:!0},dropdown:{limit:100}}},"keywords")},deleteKeywords:function(){this.sandbox.emit("husky.datagrid.items.get-selected",function(a){this.sandbox.sulu.showDeleteDialog(function(b){b===!0&&this.sandbox.util.save(this.templates.keywordsUrl({category:this.data.id,locale:this.options.locale,ids:a}),"DELETE").then(function(){for(var b=0,c=a.length;c>b;b++)this.sandbox.emit("husky.datagrid.record.remove",a[b]);this.sandbox.emit("sulu.labels.success.show",this.translations.keywordDeleteMessage,this.translations.keywordDeleteLabel)}.bind(this))}.bind(this))}.bind(this))},handleFail:function(a,b){409===a.status&&2002===a.responseJSON.code?this.handleConflict(b.id,b.keyword):409===a.status&&2001===a.responseJSON.code&&this.resolveConflict("merge",b.id,b.keyword)},handleConflict:function(a,b){var c=this.sandbox.dom.createElement("<div/>");this.$el.append(c),this.sandbox.start([{name:"overlay@husky",options:{el:c,cssClass:"alert",removeOnClose:!0,openOnStart:!0,instanceName:"warning",slides:[{title:this.translations.conflictTitle,message:this.translations.conflictMessage,okCallback:function(){this.resolveConflict("overwrite",a,b)}.bind(this),buttons:[{text:this.translations.conflictOverwrite,type:"ok",align:"right"},{text:this.translations.conflictDetach,align:"center",callback:function(){this.resolveConflict("detach",a,b),this.sandbox.emit("husky.overlay.warning.close")}.bind(this)},{type:"cancel",align:"left"}]}]}}])},resolveConflict:function(a,b,c){var d={id:b,keyword:c};this.sandbox.util.save(this.templates.keywordsUrl({id:b,category:this.data.id,locale:this.options.locale,force:a}),"PUT",d).then(function(a){a.id!==d.id?(this.sandbox.emit("husky.datagrid.record.remove",d.id),this.sandbox.emit("husky.datagrid.record.add",a)):this.sandbox.emit("husky.datagrid.records.change",d)}.bind(this)).fail(function(a){this.handleFail(a,d)}.bind(this))}}});