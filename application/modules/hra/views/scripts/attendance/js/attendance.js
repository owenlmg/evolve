// 出勤数据模型
Ext.define('Attendance', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "number"}, 
             {name: "dept"}, 
             {name: "cname"}, 
             {name: "ename"}, 
             {name: "week"}, 
             {name: "day_of_week"}, 
             {name: "type"}, 
             {name: "clock_in",type: 'date',dateFormat: 'timestamp'}, 
             {name: "clock_out",type: 'date',dateFormat: 'timestamp'}, 
             {name: "absence"}, 
             {name: "sec_late"}, 
             {name: "sec_leave"}, 
             {name: "sec_truancy_half"}, 
             {name: "sec_truancy"}, 
             {name: "clock_chk"}, 
             {name: "clock_info"}, 
             {name: "clock_hours"}, 
             {name: "remark"}, 
             {name: "ip"}, 
             {name: "pc"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 出勤信息数据源
var attendanceStore = Ext.create('Ext.data.Store', {
    model: 'Attendance',
    pageSize: 100,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/hra/attendance/getattendance/option/list'
    },
    listeners: {
    	beforeload: function(){
    		var key = Ext.getCmp('search_key').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(attendanceStore.proxy.extraParams, {
        		key: key,
                date_from: date_from,
                date_to: date_to
            });
        }
    }
});

// 出勤信息编辑插件
var attendanceRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

if(hraBtnHidden){
	attendanceRowEditing = null;
}

var timeRender = function(val, meta){
	if(val != 0 && val != '00:00:00'){
		return '<span style="color:red;">' + val + '</span>';
	}
};

var searchByMonth = function (from, to, qty) {
    var date1 = from.getValue();
    var data2 = to.getValue();
    var date3 = new Date(date1.getFullYear(), date1.getMonth() + qty, 1);
    var date4 = new Date(data2.getFullYear(), data2.getMonth() + qty + 1, 0);
    from.setValue(date3);
    to.setValue(date4);
}

// 出勤信息列表
var attendanceGrid = Ext.create('Ext.grid.Panel', {
    id: 'attendanceGrid',
    border: 0,
	title: '打卡记录',
	store: attendanceStore,
    selType: 'checkboxmodel',
    columnLines: true,
    tbar: [{
    	iconCls: 'icon-previous',
    	tooltip: '上月',
    	handler: function(){
    		searchByMonth(Ext.getCmp('search_date_from'), Ext.getCmp('search_date_to'), -1);
    		attendanceStore.loadPage(1);
    	}
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        editable: false,
        width: 120,
        id: 'search_date_from',
        emptyText: '日期从...',
        value: Ext.util.Format.date(new Date(), 'Y-m-01')
    }, {
    	xtype: 'displayfield',
    	value: '-'
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        editable: false,
        width: 120,
        id: 'search_date_to',
        emptyText: '日期至...',
        value: Ext.util.Format.date(new Date(), 'Y-m-t')
    }, {
    	iconCls: 'icon-next',
    	tooltip: '下月',
    	handler: function(){
    		searchByMonth(Ext.getCmp('search_date_from'), Ext.getCmp('search_date_to'), 1);
    		attendanceStore.loadPage(1);
    	}
    }, {
        xtype: 'textfield',
        id: 'search_key',
        emptyText: '工号/中文名/英文名...',
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                    attendanceStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	attendanceStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: hraBtnHidden,
            iconCls: 'icon-export',
            handler: function(){
                window.open(homePath+'/public/hra/attendance/getattendance/option/csv');
            }
        }]
    }, {
        text: '导入',
        hidden: hraBtnHidden,
        iconCls: 'icon-import',
        handler: function(){
        	importAttendanceWin.show();
        }
    }, {
        xtype: 'splitbutton',
        text: '添加',
        hidden: hraBtnHidden,
        iconCls: 'icon-add',
        scope: this,
        handler: function(){
            attendanceRowEditing.cancelEdit();
            
            var dateTmp = new Date();
            
            var in_time = dateTmp.getFullYear();
            
            var r = Ext.create('Attendance', {
                number: '',
                clock_in: new Date(dateTmp.getFullYear(), dateTmp.getMonth(), dateTmp.getDate(), '09'),
                clock_out: new Date(dateTmp.getFullYear(), dateTmp.getMonth(), dateTmp.getDate(), '18'),
                remark: ''
            });

            attendanceStore.insert(0, r);
            attendanceRowEditing.startEdit(0, 0);
        },
        menu: [{
            text: '删除',
            iconCls: 'icon-delete',
            handler: function(){
                var selection = Ext.getCmp('attendanceGrid').getView().getSelectionModel().getSelection();

                if(selection.length > 0){
                    attendanceStore.remove(selection);
                }else{
                    Ext.MessageBox.alert('错误', '没有选择删除对象！');
                }
            }
        }]
    }, {
        text: '保存修改',
        hidden: hraBtnHidden,
        iconCls: 'icon-save',
        scope: this,
        handler: function(){
            var updateRecords = attendanceStore.getUpdatedRecords();
            var insertRecords = attendanceStore.getNewRecords();
            var deleteRecords = attendanceStore.getRemovedRecords();
            
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
                            url: homePath+'/public/hra/attendance/editattendance',
                            params: {json: json},
                            method: 'POST',
                            success: function(response, options) {
                                var data = Ext.JSON.decode(response.responseText);
         
                                if(data.success){
                                    Ext.MessageBox.alert('提示', data.info);
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
    plugins: attendanceRowEditing,
    columns: [{
        xtype: 'rownumberer'
    }, {
    	text: 'ID',
        align: 'center',
        dataIndex: 'id',
        hidden: true,
        flex: 0.5
    }, {
        text: '工号 *',
        align: 'center',
        dataIndex: 'number',
        editor: 'textfield',
        flex: 0.8
    }, {
        text: '中文名',
        align: 'center',
        dataIndex: 'cname',
        flex: 1
    }, {
        text: '英文名',
        dataIndex: 'ename',
        flex: 1
    }, {
        text: '部门',
        dataIndex: 'dept',
        flex: 1
    }, {
        text: '周次',
        align: 'center',
        dataIndex: 'week',
        flex: 0.5
    }, {
        text: '星期',
        align: 'center',
        dataIndex: 'day_of_week',
        renderer: function(val, meta){
            if(val == 1){
                return '一';
            }else if(val == 2){
                return '二';
            }else if(val == 3){
                return '三';
            }else if(val == 4){
                return '四';
            }else if(val == 5){
                return '五';
            }else if(val == 6){
                meta.style = 'background-color: #FF8C00';
                return '<b>六</b>';
            }else{
                meta.style = 'background-color: #FF8C00';
                return '<b>日</b>';
            }
        },
        flex: 0.5
    }, {
        text: '上班时间 *',
        align: 'center',
        dataIndex: 'clock_in',
        renderer: function(val, meta, record){
        	if(val == null){
        		meta.style = 'background-color: yellow';
        	}
        	
        	return Ext.util.Format.date(val, 'Y-m-d H:i:s');
        },
        editor: Ext.create('Go.form.field.DateTime',{
            renderTo:Ext.getBody(),
            format:'Y-m-d H:i:s',
            editable: false,
            allowBlank: false,
            anchor:'100%'
        }),
        flex: 1.5
    }, {
        text: '下班时间 *',
        align: 'center',
        dataIndex: 'clock_out',
        renderer: function(val, meta, record){
        	if(val == null){
        		meta.style = 'background-color: yellow';
        	}
        	
        	return Ext.util.Format.date(val, 'Y-m-d H:i:s');
        },
        editor: Ext.create('Go.form.field.DateTime',{
            renderTo:Ext.getBody(),
            format:'Y-m-d H:i:s',
            editable: false,
            allowBlank: false,
            anchor:'100%'
        }),
        flex: 1.5
    }, {
    	text: '时长',
    	align: 'center',
    	dataIndex: 'clock_hours',
        renderer: function(val, meta){
            if(val > 0){
                return val;
            }else{
            	meta.style = 'background-color: yellow';
            }
        },
        flex: 0.75
    }, {
        text: '打卡结果',
        align: 'center',
        dataIndex: 'clock_info',
        renderer: function(val, meta){
            if(val != ''){
                meta.style = 'background-color: #FF9797';
                meta.tdAttr = 'data-qtip="' + val + '"';

                return val;
            }
        },
        flex: 1
    }, {
    	text: '缺勤时长',
    	align: 'center',
    	dataIndex: 'absence',
    	renderer: function(val, meta){
    		if(val != '00:00:00'){
                meta.style = 'color: #FF0000';
                return val;
            }
    	}
    }, {
        text: '计算机名',
        hidden: true,
        dataIndex: 'pc',
        flex: 1
    }, {
        text: 'IP地址',
        hidden: true,
        align: 'center',
        dataIndex: 'ip',
        flex: 1
    }, {
        text: '备注 *',
        dataIndex: 'remark',
        editor: 'textfield',
        flex: 2
    }, {
        text: '创建人',
        hidden: true,
        dataIndex: 'creater',
        align: 'center',
        flex: 0.5
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
        flex: 0.5
    }, {
        text: '更新时间',
        hidden: true,
        dataIndex: 'update_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        flex: 1.5
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
    	border: 0,
        store: attendanceStore,
        displayInfo: true,
        displayMsg: '显示 {0} - {1} 共 {2}',
        emptyMsg: "没有数据"
    })
});