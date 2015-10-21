// 导入考勤数据
var importBomWin = Ext.create('Ext.window.Window', {
    title: '导入BOM数据',
    layout: 'fit',
    width: 500,
    modal: true,
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [{
            xtype: 'form',
            layout: 'form',
            id: 'importForm',
            url: homePath+'/public/product/newbom/import/type/dev',
            bodyPadding: '5 5 5 5',

            items: [{
            	xtype: 'filefield',
                id: 'csv',
                name: 'csv',
                buttonText: '选择文件 (CSV)',
            	allowBlank: false/*,
            	listeners:{
                    afterrender:function(cmp){
                        cmp.fileInputEl.set({
                            multiple:'multiple'
                        });
                    }
                }*/
            }, {
                xtype: 'displayfield',
                value: '使用逗号分隔符的CSV文件，<a>模版下载</a>'
            }],

            buttons: [{
                text: '提交',
                handler: function() {
                    var form = Ext.getCmp('importForm').getForm();

                    if(form.isValid() && lib.bom.nid){
                        Ext.MessageBox.confirm('确认', '确定导入数据？', function(button, text){
                            if(button == 'yes'){
                                form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    params: {"nid" : lib.bom.nid},
                                    success: function(form, action) {
                                    	var data = action.result;

                                        if(data.success){
                                            Ext.MessageBox.alert('提示', data.info);
                                            form.reset();
                                            lib.bom.sonStore.load();
                                            lib.bom.faStore.load();
                                            importBomWin.hide();
                                        }else{
                                            Ext.MessageBox.alert('错误', data.info);
                                        }
                                    },
                                    failure: function(form, action){
                                        Ext.MessageBox.alert('错误', action.result.info);
                                    }
                                });
                            }
                        });
                    }
                }
            },{
                text: '取消',
                handler: function() {
            	    importBomWin.hide();
                    this.up('form').getForm().reset();
                }
            }]
        }]
    }]
});