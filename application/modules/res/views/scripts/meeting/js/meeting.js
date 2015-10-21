Ext.define('Meeting', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "public"},
             {name: "state",type:'int'},
             {name: "room_id"},
             {name: "room_name"},
             {name: "subject"},
             {name: "moderator",type:'int'},
             {name: "moderator_name"},
             {name: "time_from"},
             {name: "time_to"},
             {name: "members"},
             {name: "members_id"},
             {name: "members_cname"},
             {name: "members_ename"},
             {name: "number"},
             {name: "mom"},
             {name: "remark"},
             {name: "creater"},
             {name: "create_user"},
             {name: "create_time"},
             {name: "updater"},
             {name: "update_time"}]
});

var meetingStore = Ext.create('Ext.data.Store', {
    model: 'Meeting',
    pageSize: 100,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/res/meeting/getmeeting'
    },
    autoLoad: true,
    listeners: {
    	beforeload: function(){
    		var state = Ext.getCmp('search_state').getValue();
    		var key = Ext.getCmp('search_key').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(meetingStore.proxy.extraParams, {
        		key: key,
        		state: state,
                date_from: date_from,
                date_to: date_to
            });
        }
    }
});

function openMom(idx){
	momViewWin.show();
	var record = meetingStore.getAt(idx);
	var mom = '<p>'+record.get('number')+': '+record.get('subject')+'</p>';
	mom += '<hr><p>'+record.get('time_from')+' - '+record.get('time_to')+'</p>';
	mom += '<p>'+record.get('members_cname')+'</p>';
	mom += '<p>'+record.get('remark')+'</p>';
	mom += '<hr><p>'+record.get('mom')+'</p>';
	Ext.getCmp('momViewForm').getForm().findField('mom').setValue(mom);
}

var meetingGrid = Ext.create('Ext.grid.Panel', {
    id: 'meetingGrid',
	store: meetingStore,
	layout: 'fit',
    columnLines: true,
    border: 0,
    tbar: [{
        xtype: 'combobox',
        id: 'search_state',
        emptyText: '状态...',
        displayField: 'name',
        valueField: 'id',
        value: 0,
        width: 100,
        editable: false,
        store: Ext.create('Ext.data.Store', {
            fields: ['name', 'id'],
            data: [
                {"name": "开启", "id": 0},
                {"name": "结束", "id": 1},
                {"name": "取消", "id": 2}
            ]
        }),
        listeners: {
        	change: function( sel, newValue, oldValue, eOpts ){
        		meetingStore.loadPage(1);
            }
        }
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        width: 110,
        id: 'search_date_from',
        emptyText: '日期从...',
        editable: false,
        value: Ext.util.Format.date(new Date(), 'Y-m-01')
    }, {
    	xtype: 'displayfield',
    	value: '-'
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        width: 110,
        id: 'search_date_to',
        emptyText: '日期至...',
        editable: false,
        value: Ext.util.Format.date(new Date(), 'Y-m-t')
    }, {
        xtype: 'textfield',
        id: 'search_key',
        emptyText: '关键字...',
        width: 150,
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                    meetingStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	meetingStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: !meetingAdmin,
            iconCls: 'icon-export',
            handler: function(){
                window.open(homePath+'/public/res/meeting/getmeeting/option/csv');
            }
        }]
    }, {
    	xtype: 'splitbutton',
    	text: '会议预定',
    	iconCls: 'icon-add',
    	handler: function(){
    		meetingWin.show();
    		Ext.getCmp('meetingForm').getForm().findField('operate').setValue('new');
    	},
    	menu: [{
        	text: '复制会议',
        	id: 'tbar_copy',
        	disabled: true,
        	iconCls: 'icon-copy',
        	handler: function(){
        		meetingWin.show();
        		var selection = Ext.getCmp('meetingGrid').getView().getSelectionModel().getSelection();
        		var record = selection[0];
        		Ext.getCmp('meetingForm').getForm().loadRecord(record);
        		
        		Ext.getCmp('meetingForm').getForm().findField('operate').setValue('copy');
        		Ext.getCmp('meetingForm').getForm().findField('id').setValue('');
        		//Ext.getCmp('meetingForm').getForm().findField('time_from').setValue(Ext.util.Format.date(new Date(), 'Y-m-d H:00:00'));
        		//Ext.getCmp('meetingForm').getForm().findField('time_to').setValue(Ext.util.Format.date(new Date(), 'Y-m-d H:00:00'));
        	}
        }, {
        	text: '修改会议',
        	id: 'tbar_edit',
        	disabled: true,
        	iconCls: 'icon-edit',
        	handler: function(){
        		var selection = Ext.getCmp('meetingGrid').getView().getSelectionModel().getSelection();
        		var record = selection[0];
        		
        		if(record.get('create_user') != user_id && !meetingAdmin){
        			Ext.MessageBox.alert('错误', '没有权限修改当前会议！');
        		}else if(record.get('state') != 0){
        			Ext.MessageBox.alert('错误', '会议已结束，不能修改！');
        		}else{
        			meetingWin.show();
            		Ext.getCmp('meetingForm').getForm().loadRecord(record);
            		
            		Ext.getCmp('meetingForm').getForm().findField('operate').setValue('edit');
        		}
        	}
        }, {
        	text: '取消会议',
        	disabled: true,
        	id: 'tbar_cancel',
        	iconCls: 'icon-delete',
        	handler: function(){
        		var selection = Ext.getCmp('meetingGrid').getView().getSelectionModel().getSelection();
        		var record = selection[0];
        		
        		if(record.get('create_user') != user_id && !meetingAdmin){
        			Ext.MessageBox.alert('错误', '没有权限取消当前会议！');
        		}else if(record.get('state') != 0){
        			Ext.MessageBox.alert('错误', '只能取消开启的会议！');
        		}else{
        			Ext.MessageBox.confirm('确认', '确定取消会议？', function(button, text){
                        if(button == 'yes'){
                            Ext.Msg.wait('提交中，请稍后...', '提示');
                            Ext.Ajax.request({
                                url: homePath+'/public/res/meeting/cancel',
                                params: {id: record.get('id')},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);

                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        meetingStore.reload();
                                    }else{
                                        Ext.MessageBox.alert('错误', data.info);
                                    }
                                },
                                failure: function(response){
                                    Ext.MessageBox.alert('错误', '取消提交失败');
                                }
                            });
                        }
                    });
        		}
        	}
        }]
    }, {
    	text: '会议纪要',
    	id: 'tbar_mom',
    	disabled: true,
    	iconCls: 'icon-doc',
    	handler: function(){
    		momWin.show();
    		var selection = Ext.getCmp('meetingGrid').getView().getSelectionModel().getSelection();
    		var record = selection[0];
    		Ext.getCmp('momForm').getForm().loadRecord(record);
    	}
    }, {
    	text: '会议室管理',
    	disabled: !meetingAdmin,
    	iconCls: 'icon-setting',
    	handler: function(){
    		roomWin.show();
    		roomStore.load();
    	}
    }],
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(record.get('state') == 1){
                // 结束
                return 'gray-row';
            }else if(record.get('state') == 2){
                // 取消
                return 'dark-row';
            }
        }
    },
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        hidden: true,
        dataIndex: 'id',
        width: 60
    }, {
        text: '会议编号',
        dataIndex: 'number',
        align: 'right',
        width: 120,
        renderer: function(value, metaData, record, colIndex, store, view) {
        	if(record.get('mom')){
        		return '<div style="cursor:pointer;" onclick="openMom(' + colIndex + ');"><img src="'+homePath+'/public/images/icons/doc.png"></img> ' + value + '</div>';
        	}else{
        		return value;
        	}
        }
    }, {
        text: '会议室',
        align: 'center',
        dataIndex: 'room_name',
        width: 100
    }, {
        text: '主题',
        dataIndex: 'subject',
        renderer: longTextRender,
        width: 200
    }, {
        text: '主持人',
        dataIndex: 'moderator_name',
        align: 'center',
        width: 100
    }, {
        text: '时间-从',
        align: 'center',
        dataIndex: 'time_from',
        width: 140
    }, {
        text: '时间-至',
        align: 'center',
        dataIndex: 'time_to',
        width: 140
    }, {
        text: '参会人员',
        dataIndex: 'members_cname',
        renderer: longTextRender,
        width: 200
    }, {
        text: '备注',
        dataIndex: 'remark',
        renderer: longTextRender,
        flex: 1
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
        width: 140
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
        width: 150
    }],
    listeners: {
        selectionchange: function( sel, selected, eOpts ){
            if(selected.length > 0){
                Ext.getCmp('tbar_copy').enable();
                Ext.getCmp('tbar_edit').enable();
                Ext.getCmp('tbar_cancel').enable();
                Ext.getCmp('tbar_mom').enable();
            }else{
                Ext.getCmp('tbar_copy').disable();
                Ext.getCmp('tbar_edit').disable();
                Ext.getCmp('tbar_cancel').disable();
                Ext.getCmp('tbar_mom').disable();
            }
        }
    },
    bbar: [Ext.create('Ext.PagingToolbar', {
    	border: 0,
        store: meetingStore,
        displayInfo: true,
        displayMsg: '显示 {0} - {1} 共 {2}',
        emptyMsg: "没有数据"
    })]
});