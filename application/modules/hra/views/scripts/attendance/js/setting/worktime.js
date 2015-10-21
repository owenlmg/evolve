// 工作时间数据模型
Ext.define('Worktime', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "type"}, 
             {name: "active_from",type: 'date',dateFormat: 'Y-m-d'}, 
             {name: "active_to",type: 'date',dateFormat: 'Y-m-d'}, 
             {name: "work_from",type: 'date',dateFormat: 'timestamp'}, 
             {name: "work_to",type: 'date',dateFormat: 'timestamp'}, 
             {name: "rest_from",type: 'date',dateFormat: 'timestamp'}, 
             {name: "rest_to",type: 'date',dateFormat: 'timestamp'}, 
             {name: "limit_late"}, 
             {name: "limit_leave"}, 
             {name: "limit_truancy_half"}, 
             {name: "limit_truancy"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

//工作时间设置数据源
var worktimeStore = Ext.create('Ext.data.Store', {
 model: 'Worktime',
 proxy: {
     type: 'ajax',
     reader: 'json',
     url: homePath+'/public/hra/attendance/getworktime/option/list'
 }
});

// 工作时间编辑插件
var worktimeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

//工作时间管理窗口
var worktimeWin = Ext.create('Ext.window.Window', {
    title: '工作时间管理',
    border: 0,
    selType: 'checkboxmodel',
    height: 400,
    width: 1000,
    modal: true,
    constrain: true,
    layout: 'border',
    closeAction: 'hide',
    maximizable: true,
    tools: [{
        type: 'refresh',
        tooltip: '刷新列表',
        scope: this,
        handler: function(){worktimeStore.reload();}
    }],
    items: [{
        region: 'center',
        id: 'worktimeGrid',
        xtype: 'gridpanel',
        columnLines: true,
        store: worktimeStore,
        tbar: [{
            xtype: 'combobox',
            id: 'worktime_type',
            emptyText: '类别...',
            displayField: 'name',
            valueField: 'id',
            width: 100,
            editable: false,
            store: typeListStore,
            query: 'local',
            listeners: {
                change: function(combo, newValue, oldValue, eOpts){
                    if(newValue != oldValue){
                    	worktimeStore.load({
                            params: {
                                type: newValue
                            }
                        });
                    }
                }
            }
        }, {
            text: '添加',
            iconCls: 'icon-add',
            scope: this,
            handler: function(){
            	worktimeRowEditing.cancelEdit();
                
                var r = Ext.create('Worktime', {
                    active_from: Ext.Date.clearTime(new Date()),
                    active_to: Ext.Date.clearTime(new Date()),
                    type: 1,
                    limit_late: 0,
                    limit_leave: 0,
                    limit_truancy_half: 0,
                    limit_truancy: 0
                });

                worktimeStore.insert(0, r);
                worktimeRowEditing.startEdit(0, 0);
            }
        }, {
            text: '删除',
            iconCls: 'icon-delete',
            handler: function(){
            	var selection = Ext.getCmp('worktimeGrid').getView().getSelectionModel().getSelection();

                if(selection.length > 0){
                    worktimeStore.remove(selection);
                }else{
                    Ext.MessageBox.alert('错误', '没有选择删除对象！');
                }
            }
        }, {
        	text: '保存修改',
            iconCls: 'icon-save',
            scope: this,
            handler: function(){
            	var updateRecords = worktimeStore.getUpdatedRecords();
            	var insertRecords = worktimeStore.getNewRecords();
                var deleteRecords = worktimeStore.getRemovedRecords();
                
                // 判断是否有修改数据
                if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                    var changeRows = {
                            updated:    [],
                            inserted:   [],
                            deleted:    []
                    }
                    
                    for(var i = 0; i < updateRecords.length; i++){
                        changeRows.updated.push(updateRecords[i].data)
                    }
                    
                    for(var i = 0; i < insertRecords.length; i++){
                        changeRows.inserted.push(insertRecords[i].data)
                    }
                    
                    for(var i = 0; i < deleteRecords.length; i++){
                        changeRows.deleted.push(deleteRecords[i].data)
                    }
                    
                    Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                        if(button == 'yes'){
                            var json = Ext.JSON.encode(changeRows);
                            
                            Ext.Msg.wait('提交中，请稍后...', '提示');
                            Ext.Ajax.request({
                                url: homePath+'/public/hra/attendance/editworktime',
                                params: {json: json},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);
             
                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        worktimeStore.reload();
                                        attendanceStore.reload();
                                    }else{
                                        Ext.MessageBox.alert('错误', data.info);
                                    }
                                },
                                failure: function(response){
                                    Ext.MessageBox.alert('错误', '保存提交失败');
                                }
                            });
                        }
                    });
                }else{
                    Ext.MessageBox.alert('提示', '没有修改任何数据！');
                }
            }
        }],
        plugins: worktimeRowEditing,
        columns: [{
            xtype: 'rownumberer'
        }, {
            text: '用工形式',
            width: 80,
            dataIndex: 'type',
            align: 'center',
            editor: new Ext.form.field.ComboBox({
                typeAhead: true,
                editable: false,
                triggerAction: 'all',
                displayField: 'name',
                valueField: 'id',
                queryMode: 'local',
                store: typeListStore
            }),
            renderer: typeRender
        }, {
            text: '生效日期-从',
            align: 'center',
            dataIndex: 'active_from',
            renderer: Ext.util.Format.dateRenderer('Y-m-d'),
            editor: {
                xtype: 'datefield',
                editable: false,
                format: 'Y-m-d'
            },
            flex: 1.5
        }, {
            text: '生效日期-至',
            align: 'center',
            dataIndex: 'active_to',
            renderer: Ext.util.Format.dateRenderer('Y-m-d'),
            editor: {
                xtype: 'datefield',
                editable: false,
                format: 'Y-m-d'
            },
            flex: 1.5
        }, {
            text: '上班-从',
            dataIndex: 'work_from',
            align: 'center',
            renderer: Ext.util.Format.dateRenderer('H:i'), 
            editor: {
            	xtype: 'timefield',
            	editable: false,
            	minValue: '08:00',
                maxValue: '09:30',
            	format: 'H:i',
            	increment: 5
            },
            flex: 1
        }, {
            text: '下班-至',
            dataIndex: 'work_to',
            align: 'center',
            renderer: Ext.util.Format.dateRenderer('H:i'), 
            editor: {
            	xtype: 'timefield',
            	editable: false,
            	minValue: '17:00',
                maxValue: '18:30',
            	format: 'H:i',
            	increment: 5
            },
            flex: 1
        }, {
            text: '午休-从',
            dataIndex: 'rest_from',
            align: 'center',
            renderer: Ext.util.Format.dateRenderer('H:i'), 
            editor: {
            	xtype: 'timefield',
            	editable: false,
            	minValue: '11:30',
                maxValue: '12:30',
            	format: 'H:i',
            	increment: 5
            },
            flex: 1
        }, {
        	text: '午休-至',
            dataIndex: 'rest_to',
            align: 'center',
            renderer: Ext.util.Format.dateRenderer('H:i'), 
            editor: {
            	xtype: 'timefield',
            	editable: false,
            	minValue: '12:30',
                maxValue: '13:30',
            	format: 'H:i',
            	increment: 5
            },
            flex: 1
        }, {
            text: '迟到时限',
            dataIndex: 'limit_late',
            align: 'center',
            editor: 'numberfield',
            flex: 1
        }, {
            text: '早退时限',
            dataIndex: 'limit_leave',
            align: 'center',
            editor: 'numberfield',
            flex: 1
        }, {
            text: '旷工半天时限',
            dataIndex: 'limit_truancy_half',
            align: 'center',
            editor: 'numberfield',
            flex: 1.5
        }, {
            text: '旷工一天时限',
            dataIndex: 'limit_truancy',
            align: 'center',
            editor: 'numberfield',
            flex: 1.5
        }, {
            text: '备注',
            hidden: true,
            dataIndex: 'remark',
            editor: 'textfield',
            flex: 1
        }, {
            text: '创建人',
            hidden: true,
            dataIndex: 'creater',
            align: 'center',
            flex: 1
        }, {
            text: '创建时间',
            hidden: true,
            dataIndex: 'create_time',
            align: 'center',
            renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
            flex: 1.5
        }, {
            text: '更新人',
            hidden: true,
            dataIndex: 'updater',
            align: 'center',
            flex: 1
        }, {
            text: '更新时间',
            hidden: true,
            dataIndex: 'update_time',
            align: 'center',
            renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
            flex: 1.5
        }]
    }]
});