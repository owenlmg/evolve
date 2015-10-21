// 导入考勤数据
var importAttendanceWin = Ext.create('Ext.window.Window', {
    title: '导入考勤数据',
    border: 0,
    layout: 'fit',
    width: 500,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [{
            xtype: 'form',
            border: 0,
            layout: 'form',
            id: 'importForm',
            url: homePath+'/public/hra/attendance/import/type/attendance',
            bodyPadding: '5 5 5 5',

            items: [{
                xtype: 'hiddenfield',
                name: 'id',
                id: 'id'
            }, {
            	xtype: 'filefield',
                id: 'csv',
                name: 'csv',
                buttonText: '选择文件 (CSV)',
                msgTarget: 'under',
                validator: function(value){
                    var arr = value.split('.');
                    
                    if(arr[arr.length - 1] != 'csv'){
                        return '格式错误';
                    }else{
                        return true;
                    }
                }
            }, {
                xtype: 'displayfield',
                value: '必须使用<a style="color:#FF0000;"><b>英文逗号分隔符的CSV</b></a>文件，不需要包含标题列。<hr><b>格式要求：工号、打卡日期、上班时间、下班时间</b><br>例：<br>0000,2014-03-24,08:51,18:01<br>0000,2014-03-25,08:52,18:12<br>0000,2014-03-26,09:12,18:07'
            }],

            buttons: [{
                text: '提交',
                formBind: true,
                disabled: true,
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
                                            Ext.MessageBox.alert('提示', data.info);
                                            form.reset();
                                            importAttendanceWin.hide();
                                            attendanceStore.reload();
                                        }else{
                                            Ext.MessageBox.alert('错误', data.info);
                                        }
                                    },
                                    failure: function(response){
                                        Ext.MessageBox.alert('错误', '导入提交失败');
                                    }
                                });
                            }
                        });
                    }
                }
            },{
                text: '取消',
                handler: function() {
                	importAttendanceWin.hide();
                    this.up('form').getForm().reset();
                }
            }]
        }]
    }]
});