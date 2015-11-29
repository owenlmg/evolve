// 加班记录数据模型
Ext.define('Overtime', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "qty"}, 
             {name: "qty_hours"}, 
             {name: "state",type:'int'}, 
             {name: "exchange",type:'int'}, 
             {name: "apply_user",type:'int'}, 
             {name: "apply_user_name"}, 
             {name: "dept"}, 
             {name: "time_from",type: 'date',dateFormat: 'timestamp'}, 
             {name: "time_to",type: 'date',dateFormat: 'timestamp'}, 
             {name: "reason"}, 
             {name: "remark"}, 
             {name: "review_user_1",type:'int'}, 
             {name: "review_user_1_name"}, 
             {name: "review_time_1",type: 'date',dateFormat: 'timestamp'}, 
             {name: "release_user"}, 
             {name: "release_user_name"}, 
             {name: "release_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "updater"}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "review_info"}]
});

// 加班记录数据源
var overtimeStore = Ext.create('Ext.data.Store', {
    model: 'Overtime',
    pageSize: 100,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/user/attendance/getovertime/option/list'
    },
    listeners: {
    	beforeload: function(){
    		var state = Ext.getCmp('search_o_state').getValue();
    		var key = Ext.getCmp('search_o_key').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_o_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_o_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(overtimeStore.proxy.extraParams, {
        		key: key,
        		state: state,
                date_from: date_from,
                date_to: date_to
            });
        }
    }
});

var overtimeForm = Ext.create('Ext.form.Panel', {
	id: 'overtimeForm',
	border: 0,
	layout: 'form',
    url: homePath+'/public/user/attendance/overtime',
    bodyPadding: '2 2 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelAlign: 'right',
        labelWidth: 60
    },
    items: [{
    	xtype: 'hiddenfield',
    	id: 'overtime_operate',
    	name: 'operate'
    }, {
    	xtype: 'hiddenfield',
    	id: 'id',
    	name: 'id'
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [{
                xtype:'combobox',
                displayField: 'name',
                valueField: 'id',
                value: user_id,
                triggerAction: 'all',
                lazyRender: true,
                store: applyUserListStore,
                queryMode: 'local',
                name: 'apply_user',
                id: 'o_apply_user',
                fieldLabel: '申请人',
                afterLabelTextTpl: required,
                editable: false,
                allowBlank: false,
                anchor:'100%',
                listeners: {
                	change: function(field, newValue, oldValue){
                		if(Ext.getCmp('overtime_operate').getValue() != 'view'){
                			//Ext.Msg.wait('提交中，请稍后...', '提示');
                            Ext.Ajax.request({
                                url: homePath+'/public/user/attendance/getdeptmanager',
                                params: {user_id: newValue},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);
                    
                                    if(data.success){
                                    	field.up('form').getForm().findField('review_user_1').setValue(data.manager_id);
                                    }else{
                                        Ext.MessageBox.alert('错误', data.info);
                                    }
                                },
                                failure: function(response){
                                    Ext.MessageBox.alert('错误', '提交失败');
                                }
                            });
                		}
                	}
                }
            }]
        }, {
            xtype: 'container',
            flex: 1.2,
            layout: 'anchor',
            items: [
            	Ext.create('Go.form.field.DateTime',{
            	    renderTo:Ext.getBody(),
            	    id: 'overtime_time_from',
            		fieldLabel:'时间从',
            		name: 'time_from',
            		format:'Y-m-d H:i:s',
            		value: Ext.util.Format.date(new Date(), 'Y-m-d 09:00:00'),
                    allowBlank: false,
                    anchor:'100%'
            	})
            ]
        }, {
            xtype: 'container',
            flex: 1.2,
            layout: 'anchor',
            items: [
            	Ext.create('Go.form.field.DateTime',{
            	    renderTo:Ext.getBody(),
            	    id: 'overtime_time_to',
            		fieldLabel:'时间至',
            		name: 'time_to',
            		format:'Y-m-d H:i:s',
            		value: Ext.util.Format.date(new Date(), 'Y-m-d 18:00:00'),
                    allowBlank: false,
                    anchor:'100%'
            	})
            ]
        }, {
        	xtype:'hiddenfield',
            value: default_manager_id,
            name: 'review_user_1',
            id: 'o_review_user_1',
            fieldLabel: '部门经理',
            afterLabelTextTpl: required,
            allowBlank: false,
            editable: false,
            labelWidth: 60,
            //hidden: true,
            flex: 1,
            anchor:'100%'
        }]
    }, {
        xtype: 'textareafield',
        allowBlank: false,
        enableFont: false,
        name: 'reason',
        fieldLabel: '事由',
        height: 150,
        anchor: '100%'
    }, {
        xtype: 'textfield',
        name: 'remark',
        fieldLabel: '备注',
        anchor: '100%'
    }],
    buttons: [{
        text: '审核',
        disabled: true,
        tooltip: '审核人是自己才能审核',
        id: 'overtimeFormReviewBtn',
        iconCls: 'icon-accept',
        handler: openOvertimeReview
    }, {
        text: '提交',
        id: 'overtimeSubmit',
        handler: function() {
        	var form = this.up('form').getForm();

        	var month_from = Ext.util.Format.date(form.findField('time_from').getValue(), 'Y-m');
        	var month_to = Ext.util.Format.date(form.findField('time_to').getValue(), 'Y-m');
        	
        	if(month_from != month_to){
        		Ext.MessageBox.alert('错误', '日期跨月，不能提交！');
        	}else if((Ext.getCmp('overtime_operate').value != 'new_hra' && Ext.getCmp('o_review_user_1').value > 0) 
        		|| (hraAdmin == 1 && Ext.getCmp('overtime_operate').value == 'new_hra')){

                if(form.isValid()){
                    Ext.MessageBox.confirm('确认', '确定提交申请？', function(button, text){
                        if(button == 'yes'){
                        	form.submit({
                                waitMsg: '提交中，请稍后...',
                                success: function(form, action) {
                             	    var data = action.result;
                              	    
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        overtimeWin.hide();
                                        Ext.getCmp('overtimeGrid').getView().getSelectionModel().clearSelections();
                                        overtimeStore.reload();
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
        	}else{
        		Ext.MessageBox.alert('错误', '部门主管不能为空，请检查系统设置！');
        	}
            
        }
    }, {
        text: '取消',
        handler: function() {
        	overtimeWin.hide();
        }
    }]
});

// 加班申请窗口
var overtimeWin = Ext.create('Ext.window.Window', {
    title: '加班申请',
    border: 0,
    id: 'overtimeWinId',
    layout: 'fit',
    width: 650,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [overtimeForm]
    }]
});

function openOvertimeReview(){
	var selection = Ext.getCmp('overtimeGrid').getView().getSelectionModel().getSelection();

    if(selection.length != 1){
        Ext.MessageBox.alert('错误', '选择错误！');
    }else if(selection[0].get('state') != 0 && selection[0].get('state') != 2){
    	Ext.MessageBox.alert('错误', '当前记录不允许审核！');
    }else if(selection[0].get('state') == 0){
    	// 审核人
        var record = selection[0];
        reviewForm.getForm().reset();
    	
    	Ext.getCmp('review_type').setValue('overtime');
    	Ext.getCmp('reviewAttendanceWin').show();
		Ext.getCmp('review_id').setValue(record.get('id'));
		
		if(record.get('review_time_1') == null && record.get('review_user_1') == user_id){
			Ext.getCmp('review_step').setValue('review_1');
    	}
    }else if(selection[0].get('state') == 2 && hraAdmin == 1){
    	// 人事
    	var record = selection[0];
    	reviewForm.getForm().reset();
    	
    	Ext.getCmp('review_type').setValue('overtime');
    	Ext.getCmp('reviewAttendanceWin').show();
		Ext.getCmp('review_id').setValue(record.get('id'));
    	Ext.getCmp('review_step').setValue('review_hra');
    }
}

// 加班记录列表
var overtimeGrid = Ext.create('Ext.grid.Panel', {
    id: 'overtimeGrid',
    multiSelect: true,
    border: 0,
	title: '加班记录',
	store: overtimeStore,
    columnLines: true,
    tbar: [{
        xtype: 'combobox',
        id: 'search_o_state',
        emptyText: '状态...',
        displayField: 'name',
        valueField: 'id',
        width: 100,
        store: Ext.create('Ext.data.Store', {
            fields: ['name', 'id'],
            data: [
                {"name": "未审核", "id": 0},
                {"name": "拒绝", "id": 1},
                {"name": "已审核", "id": 2},
                {"name": "发布", "id": 3}
            ]
        }),
        multiSelect: true
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        //editable: false,
        width: 120,
        id: 'search_o_date_from',
        emptyText: '日期从...',
        //value: default_date_from
        value: Ext.util.Format.date(new Date(), 'Y-m-01')
    }, {
    	xtype: 'displayfield',
    	value: '-'
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        //editable: false,
        width: 120,
        id: 'search_o_date_to',
        emptyText: '日期至...',
        //value: default_date_to
        value: Ext.util.Format.date(new Date(), 'Y-m-t')
    }, {
        xtype: 'textfield',
        id: 'search_o_key',
        emptyText: '关键字...',
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	overtimeStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	overtimeStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: hraBtnHidden,
            iconCls: 'icon-export',
            handler: function(){
                window.open(homePath+'/public/user/attendance/getovertime/option/csv');
            }
        }]
    }, {
    	text: '添加记录',
    	id: 'overtimeHraAddBtn',
    	tooltip: '管理员添加记录，无需评审，直接发布。',
        hidden: hraBtnHidden,
    	iconCls: 'icon-view-expand',
    	handler: function(){
    		overtimeWin.show();
        	overtimeWin.setTitle('加班申请-添加记录');
            Ext.getCmp('overtimeSubmit').setText('添加');
            Ext.getCmp('overtimeSubmit').enable();
            Ext.getCmp('o_apply_user').bindStore(employeeListStore);
        	Ext.getCmp('overtime_operate').setValue('new_hra');
        	
        	Ext.getCmp('overtime_time_from').setMinValue('');
        	Ext.getCmp('overtime_time_to').setMinValue('');
    	}
    }, {
    	xtype: 'splitbutton',
    	hidden: true,
    	id: 'overtimeNewApplyBtn',
        text: '加班申请',
        iconCls: 'icon-add',
        handler: function(){
        	overtimeWin.show();
        	overtimeWin.setTitle('加班申请-新建');
            Ext.getCmp('overtimeSubmit').setText('提交');
            Ext.getCmp('overtimeSubmit').enable();
            Ext.getCmp('o_apply_user').bindStore(applyUserListStore);
            Ext.getCmp('overtimeForm').getForm().reset();
        	Ext.getCmp('overtime_operate').setValue('new');
        	
        	Ext.getCmp('overtime_time_from').setMinValue(Ext.util.Format.date(new Date(), 'Y-m-01 09:00:00'));
        	Ext.getCmp('overtime_time_to').setMinValue(Ext.util.Format.date(new Date(), 'Y-m-01 09:00:00'));
        },
        menu: [{
            text: '修改',
            iconCls: 'icon-edit',
            handler: function(){
            	var selection = Ext.getCmp('overtimeGrid').getView().getSelectionModel().getSelection();

                if(selection.length == 0){
                    Ext.MessageBox.alert('错误', '没有选择修改对象！');
                }else if(selection.length > 0){
                    Ext.MessageBox.alert('错误', '不允许多条记录批量修改！');
                }else if(selection[0].get('state') != 1){
                	Ext.MessageBox.alert('错误', '只允许编辑自己被拒绝的申请！');
                }else if(hraAdmin == 0 && selection[0].get('create_user') != user_id && selection[0].get('apply_user') != user_id){
                	Ext.MessageBox.alert('错误', '没有权限修改当前申请！');
                }else{
                	overtimeWin.show();
                	overtimeForm.getForm().reset();
                	overtimeWin.setTitle('加班申请-修改');
                	
                    var record = selection[0];
                	Ext.getCmp('overtime_operate').setValue('edit');
                    Ext.getCmp('overtimeSubmit').setText('修改');
                    Ext.getCmp('overtimeSubmit').enable();
                    Ext.getCmp('o_apply_user').bindStore(applyUserListStore);
                    overtimeForm.getForm().loadRecord(record);
                }
            }
        }, {
        	text: '删除',
        	tooltip: '只允许删除本人被拒绝的申请',
            iconCls: 'icon-delete',
            handler: function(){
            	var selection = Ext.getCmp('overtimeGrid').getView().getSelectionModel().getSelection();

            	var errorInfo = '';
            	
                if(selection.length == 0){
                	errorInfo = '没有选择对象！';
                }else{
                	for(var i = 0; i < selection.length; i++){
                		if(selection[i].get('state') != 1 && hraAdmin == 0){
                			errorInfo = '只允许删除被拒绝的申请！';
                		}
                	}
                	
                	if(errorInfo == ''){
                		for(var i = 0; i < selection.length; i++){
                    		if(selection[0].get('apply_user') != user_id && hraAdmin == 0){
                    			errorInfo = '没有权限删除当前申请！';
                    		}
                    	}
                	}
                }
            	
                if(errorInfo != ''){
                	Ext.MessageBox.alert('错误', errorInfo);
                }else{
                	var id = [];
                    var cnt = 0;
                    for(var i = 0; i < selection.length; i++){
                    	id.push(selection[i].get('id'));
                    	cnt++;
                    }
                    
                	Ext.MessageBox.confirm('确认', '确定删除当前' + cnt + '条申请？', function(button, text){
                        if(button == 'yes'){
                        	Ext.Msg.wait('提交中，请稍后...', '提示');
                            Ext.Ajax.request({
                                url: homePath+'/public/user/attendance/overtime/operate/delete',
                                params: {id: Ext.JSON.encode(id)},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);
                    
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        overtimeStore.loadPage(1);
                                    }else{
                                        Ext.MessageBox.alert('错误', data.info);
                                    }
                                },
                                failure: function(response){
                                    Ext.MessageBox.alert('错误', '提交失败');
                                }
                            });
                        }
                	});
                }
            }
        }]
    }, {
        text: '审核',
        disabled: true,
        tooltip: '审核人是自己才能审核',
        id: 'overtimeReviewBtn',
        iconCls: 'icon-accept',
        handler: openOvertimeReview
    }],
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        hidden: true,
        dataIndex: 'id',
        width: 60
    }, {
        text: '时长 [天]',
        align: 'center',
        dataIndex: 'qty',
        renderer: function(val, meta, record){
        	if(record.get('exchange') == 1){
        		meta.style = 'background-color: #ADADAD';
        		
        		meta.tdAttr = 'data-qtip="已调休"';
        	}
        	
        	return val;
        },
        width: 80
    }, {
        text: '时长 [小时]',
        align: 'center',
        dataIndex: 'qty_hours',
        renderer: function(val, meta, record){
        	if(record.get('exchange') == 1){
        		meta.style = 'background-color: #ADADAD';
        		
        		meta.tdAttr = 'data-qtip="已调休"';
        	}
        	
        	return val;
        },
        width: 90
    }, {
        text: '状态',
        dataIndex: 'state',
        renderer: function(val, meta, record){
        	meta.tdAttr = 'data-qtip="' + record.get('review_info') + '"';
        	
        	if(val == 0){
        		meta.style = 'background-color: #ffe2e2';
        		
        		if(record.get('review_time_1') == null){
        			if(record.get('review_user_1') == user_id){
        				meta.style = 'background-color: #ffe2e2;font-weight: bold;';
        			}
        			
        			return record.get('review_user_1_name') + ': 未审核';
        		}
        	}else if(val == 1){
        		meta.style = 'background-color: #FF4500';
        		
        		return '拒绝';
        	}else if(val == 2){
        		meta.style = 'background-color: orange';
        		
        		return '已审核';
        	}else if(val == 3){
        		meta.style = 'background-color: #DFFFDF;';
        		return '发布';
        	}
        },
        width: 120
    }, {
        text: '申请人',
        dataIndex: 'apply_user_name',
        width: 100
    }, {
        text: '部门',
        align: 'center',
        dataIndex: 'dept',
        width: 100
    }, {
        text: '时间-从',
        align: 'center',
        dataIndex: 'time_from',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }, {
        text: '时间-至',
        align: 'center',
        dataIndex: 'time_to',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }, {
        text: '部门经理',
        dataIndex: 'review_user_1_name',
        align: 'center',
        width: 100
    }, {
        text: '审核时间',
        dataIndex: 'review_time_1',
        align: 'center',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }, {
        text: '发布人',
        dataIndex: 'release_user_name',
        align: 'center',
        width: 100
    }, {
        text: '发布时间',
        dataIndex: 'release_time',
        align: 'center',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }, {
        text: '事由',
        dataIndex: 'reason',
        renderer: function(val, meta, record){
        	meta.tdAttr = 'data-qtip="' + val + '"';
        	
        	return val.replace(/<br>/ig,"");
        },
        width: 260
    }, {
        text: '备注',
        dataIndex: 'remark',
        editor: 'textfield',
        renderer: function(val, meta, record){
        	meta.tdAttr = 'data-qtip="' + val + '"';
        	
        	return val.replace(/<br>/ig,"");
        },
        width: 180
    }, {
        text: '创建人',
        hidden: true,
        dataIndex: 'creater',
        align: 'center',
        width: 100
    }, {
        text: '创建时间',
        hidden: true,
        dataIndex: 'create_time',
        align: 'center',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }, {
        text: '更新人',
        hidden: true,
        dataIndex: 'updater',
        align: 'center',
        width: 100
    }, {
        text: '更新时间',
        hidden: true,
        dataIndex: 'update_time',
        align: 'center',
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        width: 150
    }],
    listeners: {
    	selectionchange: function( sel, selected, eOpts ){
    		Ext.getCmp('overtimeReviewBtn').disable();
    		Ext.getCmp('overtimeFormReviewBtn').disable();
    		
    		if(selected.length > 0){
    			if(selected[0].get('state') == 0){
        	    	// 判断当前审核阶段，初始化审核按钮
        	    	if(selected[0].get('review_time_1') == null && selected[0].get('review_user_1') == user_id){
        	    		Ext.getCmp('overtimeReviewBtn').enable();
        	    		Ext.getCmp('overtimeFormReviewBtn').enable();
        	    	}
            	}else if(selected[0].get('state') == 2 && hraAdmin == 1){
            		Ext.getCmp('overtimeReviewBtn').enable();
            		Ext.getCmp('overtimeFormReviewBtn').enable();
            	}
    		}
        },
        itemdblclick: function( grid, record, item, index, e, eOpts ) {
        	openViewOvertime(index);
        }
    },
    bbar: [Ext.create('Ext.PagingToolbar', {
    	border: 0,
        store: overtimeStore,
        displayInfo: true,
        displayMsg: '显示 {0} - {1} 共 {2}',
        emptyMsg: "没有数据"
    }), {
    	xtype: 'displayfield',
    	value: '<b>审核流程: 申请人 > 部门经理 > 人事主管</b>'
    }]
});

// 显示查看窗口
function openViewOvertime(idx){
    overtimeWin.show();
    var record = overtimeStore.getAt(idx);

    overtimeWin.setTitle('加班申请');
    Ext.getCmp('overtimeSubmit').disable();
    Ext.getCmp('o_apply_user').bindStore(employeeListStore);
    overtimeForm.getForm().loadRecord(record);
}