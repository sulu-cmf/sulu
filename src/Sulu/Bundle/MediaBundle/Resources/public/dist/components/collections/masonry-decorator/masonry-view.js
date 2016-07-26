define(["jquery","underscore","services/sulumedia/overlay-manager","services/sulumedia/user-settings-manager"],function(a,b,c,d){"use strict";var e={unselectOnBackgroundClick:!0,selectable:!0,selectOnAction:!1,imageFormat:"190x",emptyListTranslation:"public.empty-list",fields:{image:"thumbnails",title:["title"],description:["mimeType","size"]},separators:{title:" ",description:", "},emptyIcon:"fa-coffee",noImgIcon:function(a){return"fa-file-o"}},f={masonryGridId:"masonry-grid",emptyIndicatorClass:"empty-list",itemHeadClass:"item-head",itemInfoClass:"item-info",selectedClass:"selected",loadingClass:"loading",headIconClass:"head-icon",headImageClass:"head-image",actionNavigatorClass:"action-navigator",downloadNavigatorClass:"download-navigator",playVideoNavigatorClass:"play-video-navigator"},g={emptyIndicator:['<div class="'+f.emptyIndicatorClass+'" style="display: none">','   <div class="<%= icon %> icon"></div>',"   <span><%= text %></span>","</div>"].join(""),item:['<div class="masonry-item <% if (image !== "") { %>'+f.loadingClass+'<% } %>">','   <div class="masonry-head '+f.actionNavigatorClass+'">','       <div class="<%= icon %> '+f.headIconClass+'"></div>','       <% if (image !== "") { %>','       <img ondragstart="return false;" class="'+f.headImageClass+'" src="<%= image %>"/>',"       <% } %>","   </div>",'   <div class="masonry-info">',"       <% if (!!fallbackLocale) { %>",'       <span class="badge"><%= fallbackLocale %></span>',"       <% } %>",'       <span class="title '+f.actionNavigatorClass+'"><%= title %></span><br/>','       <span class="description '+f.actionNavigatorClass+'"><%= description %></span>',"   </div>",'   <div class="masonry-footer">',"       <% if (!!selectable) { %>",'       <div class="footer-checkbox custom-checkbox"><input type="checkbox"><span class="icon"></span></div>',"       <% } %>",'       <div class="fa-cloud-download footer-download footer-icon '+f.downloadNavigatorClass+'"></div>',"       <% if (!!isVideo) { %>",'           <span class="fa-play footer-play-video footer-icon '+f.playVideoNavigatorClass+'"></span>',"       <% } %>","   </div>","</div>"].join("")},h=function(a,b,c){if(a&&b){var d=[];return b.forEach(function(b){a[b]&&d.push(a[b])}),d.join(c)}},i=function(a){var b=this.sandbox.util.extend(!1,{},a);return this.datagrid.matchings.forEach(function(a){var c=a.type===this.datagrid.types.THUMBNAILS?this.options.imageFormat:"";b[a.attribute]=this.datagrid.processContentFilter.call(this.datagrid,a.attribute,b[a.attribute],a.type,c)}.bind(this)),b},j=function(){return this.createEventName("masonry.refresh")};return function(){return{initialize:function(a,b){this.masonryGridId=f.masonryGridId+(new Date).getTime(),this.datagrid=a,this.sandbox=this.datagrid.sandbox,this.options=this.sandbox.util.extend(!0,{},e,b),this.setVariables(),this.sandbox.on(j.call(this.datagrid),function(){this.sandbox.masonry.refresh("#"+this.masonryGridId,!0)}.bind(this))},setVariables:function(){this.rendered=!1,this.$el=null,this.$items={}},render:function(a,b){this.renderMasonryContainer(b),this.initializeMasonryGrid(),this.bindGeneralDomEvents(),this.renderRecords(a.embedded,!0),this.rendered=!0},renderMasonryContainer:function(a){this.$el=this.sandbox.dom.createElement('<div class="masonry-container"/>');var b=this.sandbox.util.template(g.emptyIndicator,{text:this.sandbox.translate(this.options.emptyListTranslation),icon:this.options.emptyIcon});this.sandbox.dom.append(this.$el,b);var c=this.sandbox.dom.createElement('<div id="'+this.masonryGridId+'" class="masonry-grid"/>');this.sandbox.dom.append(this.$el,c),this.sandbox.dom.append(a,this.$el)},updateEmptyIndicatorVisibility:function(){this.datagrid.data&&this.datagrid.data.embedded&&this.datagrid.data.embedded.length>0?this.$el.find("."+f.emptyIndicatorClass).hide():this.$el.find("."+f.emptyIndicatorClass).show()},initializeMasonryGrid:function(){this.sandbox.masonry.initialize("#"+this.masonryGridId,{align:"left",direction:"left",itemWidth:190,offset:30,verticalOffset:20,possibleFilters:[f.selectedClass]})},bindGeneralDomEvents:function(){this.options.unselectOnBackgroundClick&&this.sandbox.dom.on("body","click.masonry",function(){this.deselectAllRecords()}.bind(this))},renderRecords:function(c,d){this.updateEmptyIndicatorVisibility();var e=b.map(c,function(c){var e=i.call(this,c),g=e[this.options.fields.image].url||"",j=h(e,this.options.fields.title,this.options.separators.title),k=h(e,this.options.fields.description,this.options.separators.description),l="video"===e.type,m=a.Deferred(),n=[{id:"download",name:"sulu.media.download_original",url:window.location.protocol+"//"+window.location.host+e.url},{id:"divider",divider:!0},{id:window.location.protocol+"//"+window.location.host+e.url,name:"sulu.media.copy_original",info:"sulu.media.copy_url",clickedInfo:"sulu.media.copied_url"}].concat(b.map(c.thumbnails,function(a,b){return{id:window.location.protocol+"//"+window.location.host+a,name:b,info:"sulu.media.copy_url",clickedInfo:"sulu.media.copied_url"}}));return this.renderItem(e.id,g,j,e.locale!==this.options.locale?e.locale:null,k,l,d,this.options.noImgIcon(e)),this.sandbox.start([{name:"dropdown@husky",options:{el:this.$items[e.id].find("."+f.downloadNavigatorClass),instanceName:e.id,data:n}}]),this.sandbox.once("husky.dropdown."+e.id+".rendered",function(){m.resolve()}),m}.bind(this));a.when.apply(a,e).then(function(){this.clipboard=this.sandbox.clipboard.initialize("."+f.downloadNavigatorClass+" li",{text:function(a){return a.getAttribute("data-id")}})}.bind(this))},renderItem:function(a,b,c,d,e,f,h,i){this.$items[a]=this.sandbox.dom.createElement(this.sandbox.util.template(g.item,{image:b,title:this.sandbox.util.cropMiddle(String(c),20),fallbackLocale:d,description:this.sandbox.util.cropMiddle(String(e),32),isVideo:f,domain:window.location.protocol+"//"+window.location.host,selectable:this.options.selectable,icon:i})),this.datagrid.itemIsSelected.call(this.datagrid,a)&&this.selectRecord(a),h?this.sandbox.dom.append(this.sandbox.dom.find("#"+this.masonryGridId,this.$el),this.$items[a]):this.sandbox.dom.prepend(this.sandbox.dom.find("#"+this.masonryGridId,this.$el),this.$items[a]),this.bindItemLoadingEvents(a),this.bindItemEvents(a)},bindItemEvents:function(b){this.sandbox.dom.on(this.$items[b],"click",function(a){this.sandbox.dom.stopPropagation(a),this.datagrid.itemAction.call(this.datagrid,b),this.options.selectOnAction&&this.toggleItemSelected(b)}.bind(this),"."+f.actionNavigatorClass),this.sandbox.dom.on(this.$items[b],"click",function(a){this.sandbox.dom.stopPropagation(a),c.startPlayVideoOverlay.call(this,b,d.getMediaLocale())}.bind(this),"."+f.playVideoNavigatorClass),this.options.selectable&&this.sandbox.dom.on(this.$items[b],"click",function(c){a(c.target).hasClass("husky-dropdown-trigger")||a(c.target).parents().hasClass("husky-dropdown-trigger")||(this.sandbox.dom.stopPropagation(c),this.toggleItemSelected(b))}.bind(this)),this.sandbox.on("husky.dropdown."+b+".item.click",function(a){a.url&&(window.location.href=a.url)})},bindItemLoadingEvents:function(b){this.sandbox.dom.one(a(this.$items[b]).find("."+f.headImageClass),"load",function(){this.sandbox.dom.remove(a(this.$items[b]).find("."+f.headIconClass)),this.sandbox.masonry.refresh("#"+this.masonryGridId,!0),this.sandbox.dom.removeClass(this.$items[b],f.loadingClass)}.bind(this)),this.sandbox.dom.one(a(this.$items[b]).find("."+f.headImageClass),"error",function(){this.sandbox.dom.remove(a(this.$items[b]).find("."+f.headImageClass)),this.sandbox.masonry.refresh("#"+this.masonryGridId,!0),this.sandbox.dom.removeClass(this.$items[b],f.loadingClass)}.bind(this))},toggleItemSelected:function(a){this.datagrid.itemIsSelected.call(this.datagrid,a)===!0?this.deselectRecord(a):this.selectRecord(a)},extendOptions:function(a){this.options=this.sandbox.util.extend(!0,{},this.options,a)},destroy:function(){this.sandbox.dom.off("body","click.masonry"),this.sandbox.masonry.destroy("#"+this.masonryGridId),this.sandbox.dom.remove(this.$el)},addRecord:function(a,b){this.renderRecords([a],b)},removeRecord:function(a){return this.$items[a]?(this.sandbox.dom.remove(this.$items[a]),this.sandbox.masonry.refresh("#"+this.masonryGridId,!0),this.datagrid.removeRecord.call(this.datagrid,a),this.updateEmptyIndicatorVisibility(),!0):!1},selectRecord:function(b){this.sandbox.dom.addClass(this.$items[b],f.selectedClass),a(this.$items[b]).attr("data-filter-class",JSON.stringify([f.selectedClass])),this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]',this.$items[b]),":checked")||this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]',this.$items[b]),"checked",!0),this.datagrid.setItemSelected.call(this.datagrid,b)},deselectRecord:function(b){this.sandbox.dom.removeClass(this.$items[b],f.selectedClass),a(this.$items[b]).attr("data-filter-class",JSON.stringify([])),this.sandbox.dom.is(this.sandbox.dom.find('input[type="checkbox"]',this.$items[b]),":checked")&&this.sandbox.dom.prop(this.sandbox.dom.find('input[type="checkbox"]',this.$items[b]),"checked",!1),this.datagrid.setItemUnselected.call(this.datagrid,b)},deselectAllRecords:function(){this.sandbox.util.each(this.$items,function(a){this.deselectRecord(Number(a))}.bind(this))},showSelected:function(b){var c=[],d=a(".masonry-item:not(.selected)");b?(c.push(f.selectedClass),d.hide()):d.show(),this.sandbox.masonry.updateFilterClasses("#"+this.masonryGridId),this.sandbox.masonry.filter("#"+this.masonryGridId,c)}}}});