// 审核表单
var reviewForm = Ext.create('Ext.form.Panel', {
	id: 'reviewForm',
	border: 0,
    bodyPadding: '5 5 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelWidth: 75,
		labelStyle: 'font-weight:bold',
        flex: 1
    },
    items: [{
    	xtype: 'fieldcontainer',
    	msgTarget : 'side',
        layout: 'hbox',
	        items: [{
	        xtype: 'hiddenfield',
	        name: 'review_id',
	        id: 'review_id'
	    }, {
	        xtype: 'hiddenfield',
	        name: 'review_step',
	        id: 'review_step'
	    }, {
        	xtype: 'hiddenfield',
        	id: 'review_type',
        	name: 'review_type'
        }, {
        	xtype: 'radiogroup',
        	id: 'review_operate_type',
        	fieldLabel: '审核操作',
        	allowBlank: false,
        	blankText: '请选择审批操作',
        	msgTarget : 'side',
        	afterLabelTextTpl: required,
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
    	xtype: 'htmleditor',
        height: 150,
        name: 'review_remark',
        fieldLabel: '审核意见'
    }]
});

// 审核窗口
var reviewAttendanceWin = Ext.create('Ext.window.Window', {
	title: '审核',
	border: 0,
	id: 'reviewAttendanceWin',
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
                	var review_type = Ext.getCmp('review_type').getValue();
                	
                	var url = '';
                	
                	if(review_type == 'vacation'){
                		url = homePath+'/public/user/attendance/review/reviewtype/vacation';
                	}else if(review_type == 'overtime'){
                		url = homePath+'/public/user/attendance/review/reviewtype/overtime';
                	}

                    Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                        if(button == 'yes'){
                       	 	form.submit({
                       	 		url: url,
                                waitMsg: '提交中，请稍后...',
                                success: function(form, action) {
                             	    var data = action.result;
                              	    
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        reviewAttendanceWin.hide();
                                        
                                        if(review_type == 'vacation'){
                                        	Ext.getCmp('vacationReviewBtn').disable();
                                        	Ext.getCmp('vacationFormReviewBtn').disable();
                                        	vacationWin.hide();
                                            Ext.getCmp('vacationGrid').getView().getSelectionModel().clearSelections();
                                            vacationStore.reload();
                                        }else if(review_type == 'overtime'){
                                        	Ext.getCmp('overtimeReviewBtn').disable();
                                        	Ext.getCmp('overtimeFormReviewBtn').disable();
                                        	overtimeWin.hide();
                                            Ext.getCmp('overtimeGrid').getView().getSelectionModel().clearSelections();
                                            overtimeStore.reload();
                                        }
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
            	reviewForm.getForm().reset();
            	reviewAttendanceWin.hide();
            }
        }]
    }]
});