define(function(){"use strict";var a={options:{locale:null,selectCallback:function(a){}},translations:{move:"sulu.category.move-title",moveToRoot:"sulu.category.move-to-root"},templates:{overlayContent:['<div class="move-categories-container">','   <div class="m-bottom-20">','       <label for="category-move-to-root">','           <div class="custom-radio">','                <input id="category-move-to-root" type="radio" class="form-element"/>','                <span class="icon"></span>',"            </div>","            <%= translations.moveToRoot %>","        </label>","    </div>",'    <div class="datagrid"/>',"</div>"].join("")}},b={name:"move-categories",resultKey:"categories",lastClickedCategorySettingsKey:"categoriesLastClicked"};return{defaults:a,selectedId:null,initialize:function(){this.render(),this.bindCustomEvents(),this.bindDomEvents(),this.startOverlay()},render:function(){this.$overlayContent=$(this.templates.overlayContent({translations:this.translations})),this.$overlayContainer=$("<div/>"),this.$componentContainer=this.$overlayContent.find(".datagrid"),this.$rootCheckbox=this.$overlayContent.find("#category-move-to-root"),this.$el.append(this.$overlayContainer)},startOverlay:function(){this.sandbox.start([{name:"overlay@husky",options:{el:this.$overlayContainer,instanceName:b.name,openOnStart:!0,removeOnClose:!0,skin:"medium",cssClass:b.name,slides:[{title:this.translations.move,buttons:[{type:"cancel",align:"left"},{type:"ok",align:"right",inactive:!0}],data:this.$overlayContent,okCallback:function(){this.options.selectCallback(this.selectedId),this.sandbox.stop()}.bind(this)}]}}])},bindCustomEvents:function(){this.sandbox.on("husky.datagrid."+b.name+".item.select",function(a){this.sandbox.emit("husky.overlay."+b.name+".okbutton.activate"),this.selectedId=a,this.$rootCheckbox.prop("checked",!1)}.bind(this)),this.sandbox.once("husky.overlay."+b.name+".initialized",function(){var a=$.ajax("/admin/api/categories/fields?locale="+this.options.locale,{async:!1});this.sandbox.start([{name:"datagrid@husky",options:{el:this.$componentContainer,url:"/admin/api/categories?flat=true&sortBy=name&sortOrder=asc&locale="+this.options.locale,childrenPropertyName:"hasChildren",expandIds:[this.sandbox.sulu.getUserSetting(b.lastClickedCategorySettingsKey)],pagination:!1,resultKey:b.resultKey,instanceName:b.name,matchings:a.responseJSON,viewOptions:{table:{actionIcon:"check",hideChildrenAtBeginning:!1,cropContents:!1,selectItem:{type:"radio",inFirstCell:!0}}}}}])}.bind(this))},bindDomEvents:function(){this.$rootCheckbox.change(function(){return this.$rootCheckbox.prop("checked")?(this.sandbox.emit("husky.overlay."+b.name+".okbutton.activate"),this.sandbox.emit("husky.datagrid."+b.name+".deselect.item",this.selectedId),void(this.selectedId=null)):void(this.selectedId||this.sandbox.emit("husky.overlay."+b.name+".okbutton.deactivate"))}.bind(this))}}});