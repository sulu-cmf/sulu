define(["jquery","services/suluaudiencetargeting/target-group-manager","text!/admin/target-groups/template/target-group-details.html"],function(a,b,c){return{type:"form-tab",defaults:{templates:{form:c},translations:{all:"public.all",description:"public.description",pleaseChoose:"public.please-choose",priority:"sulu_audience_targeting.priority",title:"public.title",webspaces:"sulu_audience_targeting.webspaces"}},layout:function(){return{extendExisting:!0,content:{width:this.options.preview?"fixed":"max",rightSpace:!1,leftSpace:!1}}},tabInitialize:function(){this.sandbox.on("sulu.content.changed",this.setDirty.bind(this)),this.sandbox.on("husky.toggler.is-active.changed",this.setDirty.bind(this))},parseData:function(a){return this.options.parsedData=a,a.webspaces=this.parseWebspaceForSelect(a.webspaces),a},save:function(a){a.webspaces=this.parseWebspaceSelection(this.retrieveSelectionOfSelect("#webspaces")),b.save(a).then(function(a){this.saved(a)}.bind(this))},parseWebspaceSelection:function(a){var b=[];if(!a)return b;for(var c=0;c<a.length;c++)b.push({webspaceKey:a[c]});return b},parseWebspaceForSelect:function(a){var b=[];if(!a)return a;for(var c=0;c<a.length;c++)b.push(a[c].webspaceKey);return b},getTemplate:function(){return this.templates.form({data:this.options.parsedData,translations:this.translations,translate:this.sandbox.translate})},getFormId:function(){return"#target-group-form"},retrieveSelectionOfSelect:function(b){var c=[],d=a(b);return d.length&&"undefined"!=typeof d.data("selection")&&(c=d.data("selection")),c}}});