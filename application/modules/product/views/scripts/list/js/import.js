// 导入考勤数据
var importWin = Ext.create('Ext.window.Window', {
    title: '导入物料属性',
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
            url: homePath+'/public/product/list/import',
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
                value: '使用逗号分隔符的CSV文件，<a href="' + getRootPath() + '/application/modules/product/views/scripts/list/js/template.csv' + '">模版下载</a>'
            }],

            buttons: [{
                text: '提交',
                handler: function() {
                    var form = Ext.getCmp('importForm').getForm();

                    if(form.isValid()){
                        Ext.MessageBox.confirm('确认', '确定导入数据？', function(button, text){
                            if(button == 'yes'){
                                form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    success: function(form, action) {
                                    	var data = action.result;

                                        if(data.success){
                                            var msg = data.info;
                                            if(data.error) {
                                                msg += ', 以下物料未导入：' + data.error;
                                            }
                                            Ext.MessageBox.alert('提示', msg);
                                            form.reset();
                                            importWin.hide();
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
                    importWin.hide();
                    this.up('form').getForm().reset();
                }
            }]
        }]
    }]
});