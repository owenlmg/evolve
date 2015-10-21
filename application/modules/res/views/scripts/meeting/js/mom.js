var momViewForm = Ext.create('Ext.form.Panel', {
	id: 'momViewForm',
	border: 0,
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    bodyPadding: 2,
    url: homePath+'/public/res/meeting/mom',
    items: [{
    	xtype: 'displayfield',
    	name: 'mom'
    }],
    buttons: [{
        text: '关闭',
        handler: function() {
        	momViewWin.hide();
        }
    }]
});

var momForm = Ext.create('Ext.form.Panel', {
	id: 'momForm',
    border: 0,
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    bodyPadding: 2,
    url: homePath+'/public/res/meeting/mom',
    items: [{
    	fieldLabel: '内容',
        xtype: 'ckeditor',
        name: 'mom',
        labelAlign: 'right',
        labelWidth: 50,
        labelStyle: 'font-weight:bold',
        allowBlank: true,
        CKConfig: {
            height: 200,
            //如果选择字体样式等的弹出窗口被当前window挡住，就设置这个为一个较大的值
            baseFloatZIndex: 99999,
            //图片和flash都上传到这里
            filebrowserBrowseUrl : homePath+'/library/ckeditor/ckfinder/ckfinder.html',
            filebrowserImageBrowseUrl : homePath+'/library/ckeditor/ckfinder/ckfinder.html?Type=Site',
            filebrowserFlashBrowseUrl : homePath+'/library/ckeditor/ckfinder/ckfinder.html?Type=Site',
            filebrowserUploadUrl : homePath+'/library/ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Site',
            filebrowserImageUploadUrl : homePath+'/library/ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Site',
            filebrowserFlashUploadUrl : homePath+'/library/ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Site'
        },
        anchor: '100%'
    }, {
        xtype: 'hiddenfield',
        name: 'id'
    }],
    buttons: [{
        text: '提交',
        id: 'momSubmit',
        handler: function() {
        	var form = this.up('form').getForm();
        	
        	if(form.isValid()){
                Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                    if(button == 'yes'){
                    	form.submit({
                            waitMsg: '提交中，请稍后...',
                            success: function(form, action) {
                         	    var data = action.result;
                          	    
                                if(data.success){
                                    Ext.MessageBox.alert('提示', data.info);
                                    momWin.hide();
                                    Ext.getCmp('meetingGrid').getView().getSelectionModel().clearSelections();
                                    meetingStore.reload();
                                }else{
                                    Ext.MessageBox.alert('错误', data.info);
                                }
                            },
                            failure: function(form, action) {
                                Ext.MessageBox.alert('错误', action.result.info);
                            }
                        });
                    }
                });
            }
        }
    }, {
        text: '取消',
        handler: function() {
            momWin.hide();
        }
    }]
});

var momWin = Ext.create('Ext.window.Window', {
    title: '会议纪要',
    id: 'momWin',
    layout: 'fit',
    border: 0,
    width: 920,
    height: 435,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    resizable: false,
    items: [momForm]
});

var momViewWin = Ext.create('Ext.window.Window', {
    title: '会议纪要',
    id: 'momViewWin',
    layout: 'fit',
    border: 0,
    width: 800,
    height: 350,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    items: [momViewForm]
});