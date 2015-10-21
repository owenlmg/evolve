// 请假记录数据模型
Ext.define('Vacation', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "type",type:'int'}, 
             {name: "qty"}, 
             {name: "qty_hours"}, 
             {name: "in_year_qty"}, 
             {name: "vacation_qty"}, 
             {name: "vacation_qty_used"}, 
             {name: "vacation_qty_reviewing"}, 
             {name: "vacation_qty_left"},
             {name: "type_name"}, 
             {name: "state",type:'int'}, 
             {name: "apply_user",type:'int'}, 
             {name: "apply_user_name"}, 
             {name: "dept"}, 
             {name: "time_from",type: 'date',dateFormat: 'timestamp'}, 
             {name: "time_to",type: 'date',dateFormat: 'timestamp'}, 
             {name: "reason"}, 
             {name: "work"}, 
             {name: "remark"}, 
             {name: "agent",type:'int'}, 
             {name: "agent_name"}, 
             {name: "attach_name"}, 
             {name: "attach_path"}, 
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

// 调休记录数据模型
Ext.define('Exchange', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "qty"}, 
             {name: "qty_hours"}, 
             {name: "time_from"}, 
             {name: "time_to"}, 
             {name: "reason"}, 
             {name: "work"}, 
             {name: "remark"}]
});

// 调休记录数据源
var exchangeStore = Ext.create('Ext.data.Store', {
    model: 'Exchange',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/user/attendance/getexchangelist'
    }
});

// 请假记录数据源
var vacationStore = Ext.create('Ext.data.Store', {
    model: 'Vacation',
    pageSize: 100,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/user/attendance/getvacation/option/list'
    },
    listeners: {
    	beforeload: function(){
    		var type = Ext.getCmp('search_v_type').getValue();
    		var state = Ext.getCmp('search_v_state').getValue();
    		var key = Ext.getCmp('search_v_key').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_v_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_v_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(vacationStore.proxy.extraParams, {
        		key: key,
        		type: type,
        		state: state,
                date_from: date_from,
                date_to: date_to
            });
        }
    }
});

// 调休记录列表
var exchangeGrid = Ext.create('Ext.grid.Panel', {
    id: 'exchangeGrid',
	store: exchangeStore,
	height: 300,
	border: 0,
	selType: 'checkboxmodel',
    columnLines: true,
    tbar: [{
    	text: '选择',
    	iconCls: 'icon-accept',
    	handler: function(){
    		var selection = Ext.getCmp('exchangeGrid').getView().getSelectionModel().getSelection();

            if(selection.length == 0){
            	Ext.MessageBox.alert('错误', '没有选择加班时间！');
            }else{
            	var qty_hours = parseFloat(selection[0].get('qty_hours'));
            	var exchangeOvertimeIds = selection[0].get('id');
            	var exchangeInfo = '[ ' + selection[0].get('qty_hours') + '小时 ' + selection[0].get('time_from') + ' 至 ' + selection[0].get('time_to') + ' ]';
            	
            	for(var i = 1; i < selection.length; i++){
            		qty_hours += parseFloat(selection[i].get('qty_hours'));
            		exchangeOvertimeIds += ',' + selection[i].get('id');
            		exchangeInfo += ' [ ' + selection[i].get('qty_hours') + '小时 ' + selection[i].get('time_from') + ' 至 ' + selection[i].get('time_to') + ' ]';
            	}
            	
            	Ext.getCmp('v_exchange_overtime_ids').setValue(exchangeOvertimeIds);
            	Ext.getCmp('vacationSubmit').enable();
            	vacationForm.getForm().findField('remark').setValue('对调加班时间（' + qty_hours + '小时）：' + exchangeInfo);
            	exchangeWin.hide();
            }
    	}
    }, {
    	xtype: 'displayfield',
    	value: '<span style="color:#FF0000;">[ 每条加班记录仅能用于一次调休，不允许分拆调休，请注意填写正确的请假时间。 ]</span>'
    }],
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        dataIndex: 'id',
        width: 60
    }, {
        text: '时长 [天]',
        align: 'center',
        dataIndex: 'qty',
        width: 80
    }, {
        text: '时长 [小时]',
        align: 'center',
        dataIndex: 'qty_hours',
        width: 90
    }, {
        text: '时间-从',
        align: 'center',
        dataIndex: 'time_from',
        width: 150
    }, {
        text: '时间-至',
        align: 'center',
        dataIndex: 'time_to',
        width: 150
    }, {
        text: '事由',
        dataIndex: 'reason',
        width: 260
    }, {
        text: '备注',
        dataIndex: 'remark',
        editor: 'textfield',
        width: 180
    }]
});

//调休加班时间选择窗口
var exchangeWin = Ext.create('Ext.window.Window', {
	 title: '调休-可用加班',
	 layout: 'fit',
	 width: 800,
	 border: 0,
	 modal: true,
	 constrain: true,
	 closeAction: 'hide',
	 resizable: false,
	 items: [{
	     region: 'center',
	     split: true,
	     items: [exchangeGrid]
	 }]
});

var vacationForm = Ext.create('Ext.form.Panel', {
	id: 'vacationForm',
	layout: 'form',
	border: 0,
    url: homePath+'/public/user/attendance/vacation',
    //bodyPadding: '2 2 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelAlign: 'right',
        labelWidth: 70,
        labelStyle: 'font-weight:bold'
    },
    items: [{
    	xtype: 'hiddenfield',
    	id: 'vacation_operate',
    	name: 'operate'
    }, {
    	xtype: 'hiddenfield',
    	id: 'id',
    	name: 'id'
    }, {
    	xtype: 'hiddenfield',
    	id: 'v_exchange_overtime_ids',
    	name: 'exchange_overtime_ids'
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
                id: 'v_apply_user',
                fieldLabel: '申请人',
                afterLabelTextTpl: required,
                editable: false,
                allowBlank: false,
                anchor:'100%',
                listeners: {
                	change: function(field, newValue, oldValue){
                		if(Ext.getCmp('vacation_operate').getValue() != 'view'){
                			field.up('form').getForm().findField('type').setValue('');
                    		
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
            flex: 0.8,
            layout: 'anchor',
            items: [{
                xtype:'combobox',
                typeAhead: true,
                editable: false,
                triggerAction: 'all',
                displayField: 'text',
                valueField: 'val',
                store: Ext.create('Ext.data.Store', {
                    fields: [{name: 'text'}, {name: 'val', type: 'int'}],
                    data: [
                        {"text": "事假", "val": 1},
                        {"text": "年假", "val": 2},
                        {"text": "病假", "val": 3},
                        {"text": "婚假", "val": 4},
                        {"text": "丧假", "val": 5},
                        {"text": "产假和哺乳假", "val": 6},
                        {"text": "陪产假", "val": 7},
                        {"text": "调休", "val": 8},
                        {"text": "公务外出", "val": 9},
                        {"text": "计划生育假", "val": 10},
                        {"text": "工伤假", "val": 11}
                    ]
                }),
                fieldLabel: '类别',
                labelWidth: 50,
                name: 'type',
                afterLabelTextTpl: required,
                allowBlank: false,
                anchor:'100%',
                listeners: {
                	change: function(combo, newValue, oldValue){
                		var apply_user = combo.up('form').getForm().findField('apply_user').getValue();
                		
                		if(oldValue == 8){
                			this.up('form').getForm().findField('remark').setValue('');
                			Ext.getCmp('v_exchange_overtime_ids').setValue('');
                		}
                		
                		// 当请假类别为年假时，需要用户
                		if(newValue == 2){// 年假
                			Ext.getCmp('v_qty_left_apply').setVisible(true);
                			
                			if(Ext.getCmp('vacation_operate').value == 'new'){
                				if(user_id != apply_user){
                					// 非本人：获取当前用户剩余年假
                					Ext.Msg.wait('处理中，请稍后...', '提示');
                                    Ext.Ajax.request({
                                        url: homePath+'/public/user/attendance/getleftvacation',
                                        params: {user_id: apply_user},
                                        method: 'POST',
                                        success: function(response, options) {
                                            var data = Ext.JSON.decode(response.responseText);
                            
                                            if(data.success){
                                            	Ext.getCmp('v_apply_vacation_qty_left').setValue(data.qty);
                                            	
                                            	if(data.qty == 0){
                                            		Ext.MessageBox.alert('错误', '当前用户剩余年假不足！');
                                            	}else{
                                            		Ext.Msg.hide();
                                            	}
                                            }else{
                                                Ext.MessageBox.alert('错误', data.info);
                                            }
                                        },
                                        failure: function(response){
                                            Ext.MessageBox.alert('错误', '提交失败');
                                        }
                                    });
                				}else{
                					Ext.getCmp('v_apply_vacation_qty_left').setValue(vacation_qty_left);
                				}
                				
                				Ext.getCmp('vacationSubmit').enable();
                			}
                			
                			exchangeWin.hide();
                		}else if(newValue == 8){// 调休
                			exchangeWin.show();
                			exchangeStore.load({
                	            params: {
                	            	user_id: apply_user
                	            }
                	        });
                			Ext.getCmp('vacationSubmit').disable();
                		}else{
                			if(Ext.getCmp('vacation_operate').value != 'view'){
                				Ext.getCmp('vacationSubmit').enable();
                			}
                			
                			Ext.getCmp('v_qty_left_apply').setVisible(false);
                			
                			exchangeWin.hide();
                		}
                	}
                }
            }]
        }, {
        	xtype: 'container',
        	id: 'v_qty_left_apply',
        	layout: 'anchor',
        	items: [{
        		xtype: 'displayfield',
        		id: 'v_apply_vacation_qty_left',
            	name: 'vacation_qty_left',
            	width: 105,
            	renderer: function(val){
            		if(val > 0){
            			return ' （剩余：<b><a style="color: #0000FF;">' + val + '</a></b>天）';
            		}else{
            			return ' （剩余：<b><a style="color: #FF0000;">' + val + '</a></b>天）';
            		}
            	}
        	}]
        }, {
        	xtype:'combobox',
        	flex: 1,
            displayField: 'name',
            valueField: 'id',
            labelWidth: 60,
            triggerAction: 'all',
            lazyRender: true,
            store: employeeListStore,
            queryMode: 'local',
            name: 'agent',
            fieldLabel: '代理人',
            editable: false,
            anchor:'100%'
        }, {
        	xtype:'hiddenfield',
            value: default_manager_id,
            name: 'review_user_1',
            id: 'v_review_user_1',
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
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
            xtype: 'container',
            flex: 1.2,
            layout: 'anchor',
            items: [
				Ext.create('Go.form.field.DateTime',{
				    renderTo:Ext.getBody(),
				    id: 'vacation_time_from',
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
				    id: 'vacation_time_to',
					fieldLabel:'时间至',
					name: 'time_to',
					format:'Y-m-d H:i:s',
					value: Ext.util.Format.date(new Date(), 'Y-m-d 18:00:00'),
				    allowBlank: false,
				    anchor:'100%'
				})
            ]
        }]
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [{
            	xtype: 'textareafield',
                allowBlank: false,
                border: false,
                name: 'reason',
                fieldLabel: '事由',
                height: 100,
                enableFont: false,
                anchor: '100%'
            }]
        }]
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [{
            	xtype: 'textareafield',
                allowBlank: false,
                name: 'work',
                fieldLabel: '工作交接',
                height: 100,
                enableFont: false,
                anchor: '100%'
            }]
        }]
    }, {
        xtype: 'textfield',
        name: 'remark',
        border: false,
        fieldLabel: '备注',
        anchor: '100%'
    }],
    buttons: [{
        text: '审核',
        disabled: true,
        tooltip: '审核人是自己才能审核',
        id: 'vacationFormReviewBtn',
        iconCls: 'icon-accept',
        handler: openVacationReview
    }, {
        text: '提交',
        id: 'vacationSubmit',
        handler: function() {
        	var form = this.up('form').getForm();

        	var month_from = Ext.util.Format.date(form.findField('time_from').getValue(), 'Y-m');
        	var month_to = Ext.util.Format.date(form.findField('time_to').getValue(), 'Y-m');
        	
        	if(month_from != month_to){
        		Ext.MessageBox.alert('错误', '日期跨月，不能提交！');
        	}else if((Ext.getCmp('vacation_operate').value != 'new_hra' && Ext.getCmp('v_review_user_1').value > 0) 
            	|| (hraAdmin == 1 && Ext.getCmp('vacation_operate').value == 'new_hra')){
        		
        		if(Ext.getCmp('vacation_operate').value == 8 && Ext.getCmp('v_exchange_overtime_ids').value == ''){
        			Ext.MessageBox.alert('错误', '没有选择调休对应加班时间！');
        		}else{
                    if(form.isValid()){
                        Ext.MessageBox.confirm('确认', '确定提交申请？', function(button, text){
                            if(button == 'yes'){
                            	form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    success: function(form, action) {
                                 	    var data = action.result;
                                  	    
                                        if(data.success){
                                            Ext.MessageBox.alert('提示', data.info);
                                            vacationWin.hide();
                                            Ext.getCmp('vacationGrid').getView().getSelectionModel().clearSelections();
                                            vacationStore.reload();
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
        	}else{
        		Ext.MessageBox.alert('错误', '部门主管不能为空，请检查系统设置！');
        	}
        }
    }, {
        text: '取消',
        handler: function() {
            vacationWin.hide();
        }
    }]
});

// 请假申请窗口
var vacationWin = Ext.create('Ext.window.Window', {
    title: '请假申请',
    id: 'vacationWinId',
    layout: 'fit',
    border: 0,
    width: 650,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [vacationForm]
    }]
});

// 打卡审核窗口
function openVacationReview(){
	var selection = Ext.getCmp('vacationGrid').getView().getSelectionModel().getSelection();

    if(selection.length != 1){
        Ext.MessageBox.alert('错误', '选择错误！');
    }else if(selection[0].get('state') != 0 && selection[0].get('state') != 2){
    	Ext.MessageBox.alert('错误', '当前记录不允许审核！');
    }else if(selection[0].get('state') == 0){
    	// 审核人
        var record = selection[0];
    	reviewForm.getForm().reset();
    	
    	Ext.getCmp('review_type').setValue('vacation');
    	Ext.getCmp('reviewAttendanceWin').show();
		Ext.getCmp('review_id').setValue(record.get('id'));
		
		if(record.get('review_time_1') == null && record.get('review_user_1') == user_id){
			Ext.getCmp('review_step').setValue('review_1');
    	}
    }else if(selection[0].get('state') == 2 && hraAdmin == 1){
    	// 人事
    	var record = selection[0];
    	reviewForm.getForm().reset();
    	
    	Ext.getCmp('review_type').setValue('vacation');
    	Ext.getCmp('reviewAttendanceWin').show();
		Ext.getCmp('review_id').setValue(record.get('id'));
    	Ext.getCmp('review_step').setValue('review_hra');
    }
}

var attachWin = Ext.create('Ext.window.Window', {
    title: '附件',
    modal: true,
    constrain: true,
    closeAction: 'hide',
    layout: 'fit',
    fieldDefaults: {
        labelAlign: 'left',
        labelWidth: 90,
        anchor: '100%'
    },
    items: [Ext.create('Ext.form.Panel', {
        id: 'attachForm',
        border: 0,
        url: homePath+'/public/user/attendance/editattach',
        bodyPadding: '2 2 0',
        fieldDefaults: {
            msgTarget: 'side',
            labelAlign: 'right',
            labelWidth: 60,
            anchor: '100%'
        },
        items: [{
            xtype: 'hiddenfield',
            name: 'id'
        }, {
            xtype: 'filefield',
            name: 'attach_file',
            allowBlank: false,
            fieldLabel: '附件',
            buttonText: '选择文件'
        }]
    })],
    buttons: [{
        text: '提交',
        handler: function() {
            var form = Ext.getCmp('attachForm').getForm();

            if(form.isValid()){
                form.submit({
                    waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                        var data = action.result;

                        if(data.success){
                            form.reset();
                            attachWin.hide();
                            vacationStore.loadPage(1);
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(response){
                        Ext.MessageBox.alert('错误', '保存提交失败');
                    }
                });
            }
        }
    }, {
        text: '取消',
        handler: function() {
            Ext.getCmp('attachForm').getForm().reset();
            attachWin.hide();
        }
    }]
});

// 请假记录列表
var vacationGrid = Ext.create('Ext.grid.Panel', {
    id: 'vacationGrid',
    multiSelect: true,
    border: 0,
	title: '请假记录',
	store: vacationStore,
    columnLines: true,
    tbar: [{
        xtype: 'combobox',
        id: 'search_v_type',
        emptyText: '类别...',
        displayField: 'text',
        valueField: 'val',
        width: 100,
        store: Ext.create('Ext.data.Store', {
        	fields: [{name: 'text'}, {name: 'val', type: 'int'}],
            data: [
                {"text": "事假", "val": 1},
                {"text": "年假", "val": 2},
                {"text": "病假", "val": 3},
                {"text": "婚假", "val": 4},
                {"text": "丧假", "val": 5},
                {"text": "产假和哺乳假", "val": 6},
                {"text": "陪产假", "val": 7},
                {"text": "调休", "val": 8},
                {"text": "公务外出", "val": 9},
                {"text": "计划生育假", "val": 10},
                {"text": "工伤假", "val": 11}
            ]
        }),
        editable: false,
        multiSelect: true
    }, {
        xtype: 'combobox',
        id: 'search_v_state',
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
        width: 110,
        id: 'search_v_date_from',
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
        width: 110,
        id: 'search_v_date_to',
        emptyText: '日期至...',
        //value: default_date_to
        value: Ext.util.Format.date(new Date(), 'Y-m-t')
    }, {
        xtype: 'textfield',
        id: 'search_v_key',
        emptyText: '关键字...',
        width: 150,
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                    vacationStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	vacationStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: hraBtnHidden,
            iconCls: 'icon-export',
            handler: function(){
                var type = Ext.getCmp('search_v_type').getValue();
                var state = Ext.getCmp('search_v_state').getValue();
                var key = Ext.getCmp('search_v_key').getValue();
                var date_from = Ext.Date.format(Ext.getCmp('search_v_date_from').getValue(), 'Y-m-d');
                var date_to = Ext.Date.format(Ext.getCmp('search_v_date_to').getValue(), 'Y-m-d');
                
                window.open(homePath+'/public/user/attendance/getvacation/option/csv/key/'+key+'/type/'+type+'/state/'+state+'/date_from/'+date_from+'/date_to/'+date_to);
            }
        }]
    }, {
    	text: '添加记录',
    	id: 'vacationHraAddBtn',
    	tooltip: '管理员添加记录，无需评审，直接发布。',
        hidden: hraBtnHidden,
    	iconCls: 'icon-view-expand',
    	handler: function(){
    		vacationWin.show();
    		vacationWin.setTitle('请假申请-添加记录');
            Ext.getCmp('vacationSubmit').setText('添加');
            Ext.getCmp('vacationSubmit').enable();
            Ext.getCmp('v_apply_user').bindStore(employeeListStore);
        	Ext.getCmp('vacation_operate').setValue('new_hra');
        	
        	Ext.getCmp('vacation_time_from').setMinValue('');
        	Ext.getCmp('vacation_time_to').setMinValue('');
    	}
    }, {
    	xtype: 'splitbutton',
    	hidden: true,
    	id: 'vacationNewApplyBtn',
        text: '请假申请',
        iconCls: 'icon-add',
        handler: function(){
            vacationWin.show();
            vacationWin.setTitle('请假申请-新建');
            Ext.getCmp('vacationSubmit').setText('提交');
            Ext.getCmp('vacationSubmit').enable();
            Ext.getCmp('v_qty_left_apply').setVisible(false);
            Ext.getCmp('v_apply_user').bindStore(applyUserListStore);
            Ext.getCmp('vacationForm').getForm().reset();
        	Ext.getCmp('vacation_operate').setValue('new');
        	
        	Ext.getCmp('vacation_time_from').setMinValue(Ext.util.Format.date(new Date(), 'Y-m-01 09:00:00'));
        	Ext.getCmp('vacation_time_to').setMinValue(Ext.util.Format.date(new Date(), 'Y-m-01 09:00:00'));
        },
        menu: [{
            text: '修改',
            iconCls: 'icon-edit',
            handler: function(){
            	var selection = Ext.getCmp('vacationGrid').getView().getSelectionModel().getSelection();

                if(selection.length == 0){
                    Ext.MessageBox.alert('错误', '没有选择修改对象！');
                }else if(selection.length > 1){
                    Ext.MessageBox.alert('错误', '不允许多条批量修改！');
                }else if(selection[0].get('state') != 1){
                	Ext.MessageBox.alert('错误', '只允许编辑自己被拒绝的申请！');
                }else if(hraAdmin == 0 && selection[0].get('create_user') != user_id && selection[0].get('apply_user') != user_id){
                	Ext.MessageBox.alert('错误', '没有权限修改当前申请！');
                }else{
                	vacationWin.show();
                	vacationForm.getForm().reset();
                    vacationWin.setTitle('请假申请-修改');
                	
                    var record = selection[0];
                	Ext.getCmp('vacation_operate').setValue('edit');
                    Ext.getCmp('vacationSubmit').setText('修改');
                    Ext.getCmp('vacationSubmit').enable();
                    Ext.getCmp('v_apply_user').bindStore(applyUserListStore);
                	vacationForm.getForm().loadRecord(record);
                }
            }
        }, {
        	text: '删除',
        	tooltip: '只允许删除本人被拒绝的申请',
            iconCls: 'icon-delete',
            handler: function(){
            	var selection = Ext.getCmp('vacationGrid').getView().getSelectionModel().getSelection();

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
                                url: homePath+'/public/user/attendance/vacation/operate/delete',
                                params: {id: Ext.JSON.encode(id)},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);
                    
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        vacationStore.loadPage(1);
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
        id: 'vacationReviewBtn',
        iconCls: 'icon-accept',
        handler: openVacationReview
    }, {
        text: '附件',
        id: 'attachBtn',
        tooltip: '选择请假记录上传附件',
        disabled: true,
        iconCls: 'icon-attach',
        handler: function(){
            attachWin.show();

            Ext.getCmp('attachForm').getForm().findField('id').setValue(Ext.getCmp('vacationGrid').getView().getSelectionModel().getSelection()[0].get('id'));
        }
    }, ],
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        hidden: true,
        dataIndex: 'id',
        width: 60
    }, {
        text: '类别',
        align: 'center',
        dataIndex: 'type_name',
        width: 80
    }, {
        text: '时长 [天]',
        align: 'center',
        dataIndex: 'qty',
        renderer: function(val, meta, record){
        	if(record.get('type_name') == '年假'){
        		meta.tdAttr = 'data-qtip="<b>入司第' + record.get('in_year_qty') + '年：'+record.get('vacation_qty')+'天</b><br>已使用：'+record.get('vacation_qty_used')+'天<br>审核中：'+record.get('vacation_qty_reviewing')+'天<br><b>剩余：'+record.get('vacation_qty_left')+'天</b>"';
        	}

        	return val;
        },
        width: 80
    }, {
        text: '时长 [小时]',
        align: 'center',
        dataIndex: 'qty_hours',
        renderer: function(val, meta, record){
        	if(record.get('type_name') == '年假'){
        		meta.tdAttr = 'data-qtip="<b>入司第' + record.get('in_year_qty') + '年：'+record.get('vacation_qty')+'天</b><br>已使用：'+record.get('vacation_qty_used')+'天<br>审核中：'+record.get('vacation_qty_reviewing')+'天<br><b>剩余：'+record.get('vacation_qty_left')+'天</b>"';
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
        		if(hraAdmin){
        			meta.style = 'background-color: orange; font-weight: bold';
        		}else{
        			meta.style = 'background-color: orange;';
        		}

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
        text: '代理人',
        dataIndex: 'agent_name',
        align: 'center',
        width: 80
    }, {
        text: '部门经理',
        dataIndex: 'review_user_1_name',
        align: 'center',
        width: 110
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
        width: 260
    }, {
        text: '工作交接',
        dataIndex: 'work',
        width: 260
    }, {
        text: '备注',
        dataIndex: 'remark',
        editor: 'textfield',
        width: 180
    }, {
        text: '附件',
        dataIndex: 'attach_name',
        renderer: function(val, cellmeta, record, rowIndex){
            if(val != '' && val != null){
                cellmeta.tdAttr = 'data-qtip="' + val + '"';
                return '<a target="_blank" href="../' + record.get('attach_path') + '">' + val + '</a>';
            }
        },
        width: 200
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
    		Ext.getCmp('vacationReviewBtn').disable();
    		Ext.getCmp('vacationFormReviewBtn').disable();
    		
    		if(selected.length > 0){
    			if(selected[0].get('state') == 0){
        	    	// 判断当前审核阶段，初始化审核按钮
        	    	if(selected[0].get('review_time_1') == null && selected[0].get('review_user_1') == user_id){
        	    		Ext.getCmp('vacationReviewBtn').enable();
        	    		Ext.getCmp('vacationFormReviewBtn').enable();
        	    	}else if(selected[0].get('review_time_1') != null){
        	    		Ext.getCmp('vacationReviewBtn').enable();
        	    		Ext.getCmp('vacationFormReviewBtn').enable();
        	    	}
            	}else if(selected[0].get('state') == 2 && hraAdmin == 1){
            		Ext.getCmp('vacationReviewBtn').enable();
            		Ext.getCmp('vacationFormReviewBtn').enable();
            	}
    		}
    		
    		if(selected.length == 1){
    			Ext.getCmp('attachBtn').enable();
    		}else{
    			Ext.getCmp('attachBtn').disable();
    		}
        },
        itemdblclick: function( grid, record, item, index, e, eOpts ) {
        	openViewVacation(index);
        }
    },
    bbar: [Ext.create('Ext.PagingToolbar', {
    	border: 0,
        store: vacationStore,
        displayInfo: true,
        displayMsg: '显示 {0} - {1} 共 {2}',
        emptyMsg: "没有数据"
    }), {
    	xtype: 'displayfield',
    	value: '<b>审核流程: 申请人 > 部门经理 > 人事主管</b>'
    }]
});

//显示查看窗口
function openViewVacation(idx){
    vacationWin.show();
    var record = vacationStore.getAt(idx);
    var form = vacationForm.getForm();

    Ext.getCmp('v_qty_left_apply').setVisible(true);
    vacationWin.setTitle('请假申请 - 查看');
    Ext.getCmp('v_apply_user').bindStore(employeeListStore);
    form.loadRecord(record);
    form.findField('type').setValue(record.get('type'));
    Ext.getCmp('vacation_operate').setValue('view');
    Ext.getCmp('vacationSubmit').disable();
}