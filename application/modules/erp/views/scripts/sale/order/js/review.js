// 审核表单
var reviewForm = Ext.create('Ext.form.Panel', {
	id: 'reviewForm',
	border: 0,
    url: homePath+'/public/erp/sale_order/review',
    bodyPadding: '5 5 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelWidth: 75,
		labelStyle: 'font-weight:bold',
        flex: 1
    },
    items: [{
        xtype: 'hiddenfield',
        name: 'review_transfer',
        id: 'review_transfer'
    }, {
        xtype: 'hiddenfield',
        name: 'review_id',
        id: 'review_id'
    }, {
        xtype: 'hiddenfield',
        name: 'review_type_id',
        id: 'review_type_id'
    }, {
        xtype: 'hiddenfield',
        name: 'review_current_step',
        id: 'review_current_step'
    }, {
        xtype: 'hiddenfield',
        name: 'review_last_step',
        id: 'review_last_step'
    }, {
        xtype: 'hiddenfield',
        name: 'review_to_finish',
        id: 'review_to_finish'
    }, {
        xtype: 'hiddenfield',
        name: 'review_next_step',
        id: 'review_next_step'
    }, {
    	xtype: 'fieldcontainer',
    	msgTarget : 'side',
        layout: 'hbox',
        items: [{
        	xtype: 'radiogroup',
        	id: 'review_operate_type',
        	fieldLabel: '审核操作',
        	allowBlank: false,
        	blankText: '请选择审批操作',
        	afterLabelTextTpl: required,
        	flex: 2,
            items: [{
                boxLabel: '批准',
                inputValue: 'ok',
                name: 'review_operate'
            }, {
                boxLabel: '拒绝',
                inputValue: 'no',
                name: 'review_operate'
            }]
        }]
    }, {
    	xtype: 'textareafield',
    	width: 475,
        height: 150,
        name: 'review_remark',
        fieldLabel: '审核意见'
    }]
});

// 审核窗口
var reviewOrderWin = Ext.create('Ext.window.Window', {
	title: '审核',
	border: 0,
	id: 'reviewOrderWin',
	width: 500,
    modal: true,
    constrain: true,
    layout: 'fit',
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [reviewForm],
        buttons: [{
            text: '提交',
            id: 'review_sub',
            handler: function() {
                var form = reviewForm.getForm();

                if(form.isValid()){
                	var operateType = Ext.getCmp('review_operate_type').getValue().review_operate;
                    
                    Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                        if(button == 'yes'){
                       	 	form.submit({
                                waitMsg: '提交中，请稍后...',
                                success: function(form, action) {
                             	    var data = action.result;
                              	    
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        reviewOrderWin.hide();
                                        orderWin.hide();
                                        transferContentWin.hide();
                                        Ext.getCmp('reviewBtn').disable();
                                        Ext.getCmp('orderGrid').getView().getSelectionModel().clearSelections();
                                        orderStore.reload();
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
                }else{
                	Ext.MessageBox.alert('错误', "请选择审核操作类型!");
                }
            }
        }, {
            text: '取消',
            handler: function() {
            	reviewForm.getForm().reset();
                reviewOrderWin.hide();
            }
        }]
    }]
});