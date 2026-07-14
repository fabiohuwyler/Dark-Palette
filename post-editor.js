(function (plugins, editPost, data, element, components, i18n) {
'use strict';
var el=element.createElement, __=i18n.__;
var Panel=editPost.PluginDocumentSettingPanel;
var Toggle=components.ToggleControl, Select=components.SelectControl, Textarea=components.TextareaControl;
function DarkModePanel(){
 var meta=data.useSelect(function(select){return select('core/editor').getEditedPostAttribute('meta')||{};},[]);
 var edit=data.useDispatch('core/editor').editPost;
 var disabled=Boolean(meta._dark_palette_disabled);
 var behavior=meta._dark_palette_disabled_behavior||'';
 var message=meta._dark_palette_disabled_message||'';
 function save(patch){edit({meta:Object.assign({},meta,patch)});}
 var children=[el(Toggle,{key:'toggle',label:__('Disable dark mode for this entry','dark-palette'),help:disabled?__('This entry will always use the light appearance.','dark-palette'):__('Visitors may use Light, Dark or Auto on this entry.','dark-palette'),checked:disabled,onChange:function(v){save({_dark_palette_disabled:v});}})];
 if(disabled){
  children.push(el(Select,{key:'behavior',label:__('When dark mode is unavailable','dark-palette'),value:behavior,options:[{label:__('Use global default','dark-palette'),value:''},{label:__('Hide the toggle','dark-palette'),value:'hide'},{label:__('Show an information message','dark-palette'),value:'message'},{label:__('Show a disabled toggle and message','dark-palette'),value:'disabled'}],onChange:function(v){save({_dark_palette_disabled_behavior:v});}}));
  children.push(el(Textarea,{key:'message',label:__('Custom message','dark-palette'),help:__('Leave empty to use the predefined global message.','dark-palette'),value:message,onChange:function(v){save({_dark_palette_disabled_message:v});}}));
 }
 return el(Panel,{name:'dark-palette-settings',title:__('Dark Palette','dark-palette'),className:'dark-palette-document-settings'},children);
}
plugins.registerPlugin('dark-palette-post-settings',{render:DarkModePanel,icon:'admin-appearance'});
})(window.wp.plugins,window.wp.editPost,window.wp.data,window.wp.element,window.wp.components,window.wp.i18n);
