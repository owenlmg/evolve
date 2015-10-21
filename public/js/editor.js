document.write('<script language="javascript" src="'+homePath+'/library/ckeditor/ckeditor.js"></script>');
document.write('<script language="javascript" src="'+homePath+'/library/ckeditor/ckfinder/ckfinder.js"></script>');

//整合Ext TextArea和CkEditor
Ext.define('Ext.ux.form.CkEditor', {
    extend: 'Ext.form.field.TextArea',
    alias: 'widget.ckeditor',
    onRender : function(ct, position){
        if(!this.el){
            this.defaultAutoCreate = {
                tag: "textarea",
                autocomplete: "off"
            };
        }
        this.callParent(arguments);
        this.editor = CKEDITOR.replace(this.inputEl.id, this.CKConfig);
    },
    setValue: function(value) {
        this.callParent(arguments);
        if(this.editor){
            this.editor.setData(value);
        }
    },
    getValue: function() {
        if (this.editor) {
            this.editor.updateElement();
            return this.editor.getData()
        } else {
            return ''
        }
    },
    getRawValue: function() {
        if (this.editor) {
            this.editor.updateElement();
            return this.editor.getData()
        } else {
            return ''
        }
    }
});