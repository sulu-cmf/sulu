define(["underscore","jquery","services/husky/util","services/husky/mediator"],function(a,b,c,d){"use strict";function e(){}var f=null,g=a.template("/admin/api/categories<% if (!!id) { %>/<%= id %><% } %>?locale=<%= locale %>");return e.prototype={load:function(a,c){return b.ajax(g({id:a,locale:c}))},save:function(a,c){return b.ajax(g({id:a.id,locale:c}),{method:a.id?"PUT":"POST",data:a})},"delete":function(a,c){return b.ajax(g({id:a,locale:c}),{method:"DELETE"})}},e.getInstance=function(){return null===f&&(f=new e),f},e.getInstance()});