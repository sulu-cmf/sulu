define(["type/default"],function(a){"use strict";var b=function(a){return App.dom.find(".condition-row",a)};return function(c,d){var e={},f={setValue:function(a){},getValue:function(){var a=b(c),d=[];return a.each(function(){var a=$(this),b=a.find("[data-condition-id]").val(),c=a.find("[data-condition-type]").data("selection")[0],e={};c&&(a.find("[data-condition-name]").each(function(a,b){var c=$(b);e[c.attr("data-condition-name")]=c.data("selectionValues")[0]||c.data("singleInternalLink")||c.val()}),d.push({id:b||null,type:c,condition:e}))}),d},needsValidation:function(){return!1},validate:function(){return!0}};return new a(c,e,d,"conditionList",f)}});