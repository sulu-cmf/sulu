define([],function(){"use strict";return{name:"Sulu Security Permissions Form",templates:["/admin/security/template/permission/form"],view:!0,initialize:function(){this.saved=!0,this.formId="#permissions-form",this.selectedRoles=[],this.deselectedRoles=[],this.passwordField1Id="#husky-password-fields-instance1-password1",this.passwordField2Id="#husky-password-fields-instance1-password2",this.options.data?(this.user=this.options.data.user,this.contact=this.options.data.contact,this.roles=this.options.data.roles):this.sandbox.logger.log("no data given"),this.render(),this.initializePasswordFields(),this.initializeRoles(),this.bindDOMEvents(),this.bindCustomEvents(),this.initializeHeaderbar(),this.sandbox.form.create(this.formId)},addConstraintsToPasswordFields:function(){setTimeout(function(){this.sandbox.form.addConstraint(this.formId,this.passwordField1Id,"required",{required:!0}),this.sandbox.form.addConstraint(this.formId,this.passwordField2Id,"required",{required:!0})}.bind(this),10)},initializeHeaderbar:function(){this.currentType="",this.currentState="",this.setHeaderBar(!0),this.listenForChange()},setHeaderBar:function(a){if(a!==this.saved){var b=this.options.data&&this.options.data.id?"edit":"add";this.sandbox.emit("sulu.edit-toolbar.content.state.change",b,a,!0)}this.saved=a},listenForChange:function(){this.sandbox.dom.on(this.formId,"change",function(){this.setHeaderBar(!1)}.bind(this),"select, input"),this.sandbox.dom.on(this.formId,"keyup",function(){this.setHeaderBar(!1)}.bind(this),"input"),this.sandbox.util.each(this.roles,function(a,b){this.sandbox.on("husky.dropdown.multiple.select.languageSelector"+b.id+".selected.item",function(){this.setHeaderBar(!1)},this),this.sandbox.on("husky.dropdown.multiple.select.languageSelector"+b.id+".deselected.item",function(){this.setHeaderBar(!1)},this)}.bind(this))},render:function(){var a,b="";this.contact.emails&&this.contact.emails.length>0&&(b=this.contact.emails[0].email),a=this.contact?this.contact.firstName+" "+this.contact.lastName:this.sandbox.translate("security.permission.title"),this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/security/template/permission/form",{user:this.user?this.user:null,email:b,headline:a}))},initializePasswordFields:function(){this.sandbox.start([{name:"password-fields@husky",options:{instanceName:"instance1",el:"#password-component",labels:{inputPassword1:this.sandbox.translate("security.permission.password"),inputPassword2:this.sandbox.translate("security.permission.passwordRepeat"),generateLabel:this.sandbox.translate("security.permission.generatePassword")},validation:this.formId}}]),this.user&&this.user.id||this.addConstraintsToPasswordFields()},bindDOMEvents:function(){this.sandbox.dom.on("#rolesTable","click",function(a){var b=this.sandbox.dom.attr(a.currentTarget,"id");"selectAll"===b?this.selectAll(a.currentTarget):this.selectItem(a.currentTarget)}.bind(this),'input[type="checkbox"]')},selectAll:function(a){var b,c=this.sandbox.dom.find('tr td:first-child() input[type="checkbox"]',"#rolesTable");this.selectedRoles.length===this.roles.length?(this.sandbox.dom.removeClass(a,"is-selected"),this.sandbox.dom.prop(a,"checked",!1),this.sandbox.util.each(c,function(a,b){this.sandbox.dom.removeClass(b,"is-selected"),this.sandbox.dom.prop(b,"checked",!1)}.bind(this)),this.selectedRoles=[],this.sandbox.logger.log(this.selectedRoles,"selected roles")):(this.sandbox.dom.addClass(a,"is-selected"),this.sandbox.dom.prop(a,"checked",!0),this.sandbox.util.each(c,function(a,c){b=this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent(c)),"id"),this.selectedRoles.indexOf(b)<0&&this.selectedRoles.push(b),this.sandbox.dom.addClass(c,"is-selected"),this.sandbox.dom.prop(c,"checked",!0)}.bind(this)),this.sandbox.logger.log(this.selectedRoles,"selected roles"))},selectItem:function(a){var b=this.sandbox.dom.data(this.sandbox.dom.parent(this.sandbox.dom.parent(a)),"id"),c=this.selectedRoles.indexOf(b);c>=0?(this.sandbox.dom.removeClass(a,"is-selected"),this.sandbox.dom.prop(a,"checked",!1),this.selectedRoles.splice(c,1),this.deselectedRoles.indexOf(b)<0&&this.deselectedRoles.push(b),this.sandbox.logger.log(b,"role deselected")):(this.sandbox.dom.addClass(a,"is-selected"),this.sandbox.dom.prop(a,"checked",!0),this.selectedRoles.push(b),this.sandbox.logger.log(b,"role selected"))},bindCustomEvents:function(){this.sandbox.on("sulu.edit-toolbar.delete",function(){this.sandbox.emit("sulu.user.permissions.delete",this.contact.id)},this),this.sandbox.on("sulu.user.permissions.saved",function(a){this.user=a,this.user.id&&this.sandbox.form.element.hasConstraint(this.passwordField1Id,"required")&&(this.sandbox.form.deleteConstraint(this.formId,this.passwordField1Id,"required"),this.sandbox.form.deleteConstraint(this.formId,this.passwordField2Id,"required")),this.setHeaderBar(!0)},this),this.sandbox.on("sulu.edit-toolbar.save",function(){this.save()},this),this.sandbox.on("sulu.edit-toolbar.back",function(){this.sandbox.emit("sulu.contacts.contacts.list")},this)},save:function(){var a;this.getPassword(),this.sandbox.form.validate(this.formId)&&(this.sandbox.logger.log("validation succeeded"),a={user:{username:this.sandbox.dom.val("#username"),contact:this.contact,locale:this.sandbox.dom.val("#locale")},selectedRolesAndConfig:this.getSelectedRolesAndLanguages(),deselectedRoles:this.deselectedRoles},this.user&&this.user.id&&(a.user.id=this.user.id),this.password&&""!==this.password&&(a.user.password=this.password),this.sandbox.emit("sulu.user.permissions.save",a))},isValidPassword:function(){return this.user&&this.user.id?!0:!!this.password&&""!==this.password},getSelectedRolesAndLanguages:function(){var a,b,c=[];return this.sandbox.util.each(this.selectedRoles,function(d,e){a=this.sandbox.dom.find("#languageSelector"+e),b={},b.roleId=e,this.sandbox.emit("husky.dropdown.multiple.select.languageSelector"+e+".getChecked",function(a){b.selection=a}),c.push(b)}.bind(this)),c},getPassword:function(){this.sandbox.emit("husky.password.fields.instance1.get.passwords",function(a){this.password=a}.bind(this))},initializeRoles:function(){this.getSelectRolesOfUser();var a,b=this.sandbox.dom.$("#permissions-grid"),c=this.sandbox.dom.createElement("<table/>",{"class":"table matrix",id:"rolesTable"}),d=this.prepareTableHeader(),e=this.prepareTableContent(),f=this.sandbox.dom.append(c,d);f=this.sandbox.dom.append(f,e),this.sandbox.dom.html(b,f),a=this.sandbox.dom.find("tbody tr","#rolesTable"),this.sandbox.util.each(a,function(a,b){var c=this.sandbox.dom.data(b,"id"),d=this.getUserRoleLocalesWithRoleId(c);this.sandbox.start([{name:"dropdown-multiple-select@husky",options:{el:"#languageSelector"+c,instanceName:"languageSelector"+c,defaultLabel:this.sandbox.translate("security.permission.role.chooseLanguage"),checkedAllLabel:this.sandbox.translate("security.permission.role.allLanguages"),value:"name",data:["Deutsch","English","Spanish","Italienisch"],preSelectedElements:d}}])}.bind(this))},getUserRoleLocalesWithRoleId:function(a){var b;return this.user&&this.user.userRoles&&this.sandbox.util.each(this.user.userRoles,function(c,d){return d.role.id===a?(b=d.locales,!1):void 0}.bind(this)),b?b:[]},getSelectRolesOfUser:function(){this.user&&this.user.userRoles&&this.sandbox.util.each(this.user.userRoles,function(a,b){this.selectedRoles.push(b.role.id)}.bind(this))},prepareTableHeader:function(){return this.template.tableHead(this.sandbox.translate("security.permission.role.title"),this.sandbox.translate("security.permission.role.language"),this.sandbox.translate("security.permission.role.permissions"))},prepareTableContent:function(){var a=this.sandbox.dom.createElement("<tbody/>"),b=[];return this.sandbox.util.each(this.roles,function(a,c){b.push(this.prepareTableRow(c))}.bind(this)),this.sandbox.dom.append(a,b)},prepareTableRow:function(a){var b;return b=this.selectedRoles.indexOf(a.id)>=0?this.template.tableRow(a.id,a.name,!0):this.template.tableRow(a.id,a.name,!1)},template:{tableHead:function(a,b,c){return["<thead>",'<tr><th width="5%"><input id="selectAll" type="checkbox" class="custom-checkbox"/><span class="custom-checkbox-icon"></span></th>','<th width="30%">',a,"</th>",'<th width="45%">',b,"</th>",'<th width="20%">',c,"</th>","</tr>","</thead>"].join("")},tableRow:function(a,b,c){var d;return d=c?['<tr data-id="',a,'">','<td><input type="checkbox" class="custom-checkbox is-selected" checked/><span class="custom-checkbox-icon"></span></td>',"<td>",b,"</td>",'<td class="m-top-15" id="languageSelector',a,'"></td>',"<td></td>","</tr>"].join(""):['<tr data-id="',a,'">','<td><input type="checkbox" class="custom-checkbox"/><span class="custom-checkbox-icon"></span></td>',"<td>",b,"</td>",'<td class="m-top-15" id="languageSelector',a,'"></td>',"<td></td>","</tr>"].join("")}}}});