define(["services/husky/translator","text!sulumediacss/ckeditor/media-link-plugin.css"],function(a,b){"use strict";var c=function(a){var b=a.getStartElement(),c=b.getAscendant("sulu:media",!0);return c&&c.is("sulu:media")?{title:c.getText(),id:c.getAttribute("id")}:{title:a.getSelectedText()}},d=function(a,b,c){var d=b.getStartElement();d&&d.is("sulu:media")||(d=a.document.createElement("sulu:media"),a.insertElement(d)),d.setAttribute("id",c.id),d.setText(c.title),d.removeAttribute("removed"),a.fire("change")},e=function(a,b){var d=c(b),e=b.getStartElement(),f=e.getAscendant("sulu:media",!0);f.remove(),a.insertText(d.title)};return function(f){return{tagName:"sulu:media",init:function(a){this.extendCkEditorDtd(),a.addCommand("mediaLinkDialog",this.getMediaLinkDialogCommand(a)),a.addCommand("removeMediaLink",this.getRemoveMediaLinkCommand(a)),a.ui.addButton("MediaLink",{label:f.translate("sulu-media.ckeditor.media-link"),command:"mediaLinkDialog",icon:"/bundles/sulumedia/img/icon_link_media.png"}),a.contextMenu&&(this.addSuluMenuGroup(a),a.contextMenu.addListener(function(a){return a.getAscendant("sulu:media",!0)?{mediaLinkItem:CKEDITOR.TRISTATE_OFF,removeMediaLinkItem:CKEDITOR.TRISTATE_OFF}:void 0}))},extendCkEditorDtd:function(){CKEDITOR.dtd[this.tagName]=1,CKEDITOR.dtd.body[this.tagName]=1,CKEDITOR.dtd.div[this.tagName]=1,CKEDITOR.dtd.li[this.tagName]=1,CKEDITOR.dtd.p[this.tagName]=1,CKEDITOR.dtd.$block[this.tagName]=1,CKEDITOR.dtd.$removeEmpty[this.tagName]=1},getMediaLinkDialogCommand:function(a){return{dialogName:"mediaLinkDialog",allowedContent:"sulu:media[title,removed,!id]",requiredContent:"sulu:media[id]",exec:function(){var b=$("<div/>"),g=c(a.getSelection());$("#content").append(b),f.start([{name:"media-selection/overlay@sulumedia",options:{el:b,webspace:a.config.webspace,locale:a.config.locale,preselected:g.id?[g]:[],removeable:!!g.id,instanceName:"media-link",translations:{title:"sulu-media.ckeditor.media-link",save:"sulu-media.ckeditor.media-link.dialog.save",remove:"sulu-media.ckeditor.media-link.dialog.remove",selectedTitle:"sulu-media.ckeditor.media-link.dialog.selected-title"},removeOnClose:!0,openOnStart:!0,singleSelect:!0,saveCallback:function(c){f.stop(b),g.id=c[0].id,g.title=g.title?g.title:c[0].title,d(a,a.getSelection(),g)},removeCallback:function(){e(a,a.getSelection())}}}])}}},getRemoveMediaLinkCommand:function(a){return{exec:function(){e(a,a.getSelection())},refresh:function(){var b=a.getSelection(),c=b.getStartElement();return c.getAscendant("sulu:media",!0)?void this.setState(CKEDITOR.TRISTATE_OFF):void this.setState(CKEDITOR.TRISTATE_DISABLED)},contextSensitive:1,startDisabled:1}},addSuluMenuGroup:function(a){a.addMenuGroup("suluGroup"),a.addMenuItem("mediaLinkItem",{label:f.translate("sulu-media.ckeditor.media-link.edit"),icon:"/bundles/sulumedia/img/icon_link_media.png",command:"mediaLinkDialog",group:"suluGroup"}),a.addMenuItem("removeMediaLinkItem",{label:f.translate("sulu-media.ckeditor.media-link.edit.remove"),icon:"/bundles/sulumedia/img/icon_remove_link_media.png",command:"removeMediaLink",group:"suluGroup"})},onLoad:function(){CKEDITOR.addCss(_.template(b,{translations:{unpublished:a.translate("content.text_editor.error.unpublished"),removed:a.translate("content.text_editor.error.removed")}}))}}}});