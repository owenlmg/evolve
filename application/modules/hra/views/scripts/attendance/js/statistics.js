// 月度考勤统计数据模型
Ext.define('Statistics', {
    extend: 'Ext.data.Model',
    fields: [{name: "employment_type"}, 
             {name: "number"}, 
             {name: "cname"}, 
             {name: "ename"}, 
             {name: "dept_name"}, 
             {name: "post_name"}, 
             {name: "active",type:"int"}, 
             {name: "workday_qty"}, 
             {name: "attendance_qty"}, 
             {name: "workhour_qty"}, 
             {name: "attendance_hour_qty"}, 
             {name: "holiday_qty"}, 
             {name: "v_personal_qty"}, 
             {name: "v_vacation_qty"}, 
             {name: "v_sick_qty"}, 
             {name: "v_marriage_qty"}, 
             {name: "v_funeral_qty"}, 
             {name: "v_childbirth_qty"}, 
             {name: "v_childbirth_with_qty"}, 
             {name: "v_other_qty"}, 
             {name: "o_workday_qty"}, 
             {name: "o_restday_qty"}, 
             {name: "o_holiday_qty"}, 
             {name: "late_qty"}, 
             {name: "leave_early_qty"}, 
             {name: "absence_halfday_qty"}, 
             {name: "absence_qty"}]
});

// 月度考勤统计数据源
var statisticsStore = Ext.create('Ext.data.Store', {
	model: 'Statistics',
    pageSize: 100,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/hra/attendance/getstatistics'
    },
    listeners: {
    	beforeload: function(){
    		var key = Ext.getCmp('search_s_key').getValue();
    		var employment_type = Ext.getCmp('search_s_employment_type').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_s_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_s_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(statisticsStore.proxy.extraParams, {
        		key: key,
        		employment_type: employment_type,
                date_from: date_from,
                date_to: date_to
            });
        }
    }
});

var qtyRenderer = function(val, meta){
	if(val > 0){
		meta.style = 'color: #FF0000; font-weight: bold';
		return val;
	}
};

//月度考勤统计窗口
var statisticsGrid = Ext.define('KitchenSink.view.grid.LockingGrid', {
	extend: 'Ext.grid.Panel',
    xtype: 'locking-grid',
    border: 0,
    title: '考勤统计',
    layout: 'fit',
    columnLines: true,
    store: statisticsStore,
    tbar: [{
        xtype: 'combobox',
        id: 'search_s_employment_type',
        value: 1,
        displayField: 'name',
        valueField: 'id',
        width: 100,
        store: typeListStore,
        queryMode: 'local',
        editable: false
    }, {
    	iconCls: 'icon-previous',
    	tooltip: '上月',
    	handler: function(){
    		searchByMonth(Ext.getCmp('search_s_date_from'), Ext.getCmp('search_s_date_to'), -1);
    		statisticsStore.loadPage(1);
    	}
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        editable: false,
        width: 110,
        id: 'search_s_date_from',
        emptyText: '日期从...',
        value: Ext.util.Format.date(new Date(), 'Y-m-01')
    }, {
    	xtype: 'displayfield',
    	value: '-'
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        editable: false,
        width: 110,
        id: 'search_s_date_to',
        emptyText: '日期至...',
        value: Ext.util.Format.date(new Date(), 'Y-m-t')
    }, {
    	iconCls: 'icon-next',
    	tooltip: '下月',
    	handler: function(){
    		searchByMonth(Ext.getCmp('search_s_date_from'), Ext.getCmp('search_s_date_to'), 1);
    		statisticsStore.loadPage(1);
    	}
    }, {
        xtype: 'textfield',
        id: 'search_s_key',
        emptyText: '关键字...',
        width: 150,
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	statisticsStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	statisticsStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: hraBtnHidden,
            iconCls: 'icon-export',
            handler: function(){
            	var key = Ext.getCmp('search_s_key').getValue();
        		var employment_type = Ext.getCmp('search_s_employment_type').getValue();
                var date_from = Ext.Date.format(Ext.getCmp('search_s_date_from').getValue(), 'Y-m-d');
                var date_to = Ext.Date.format(Ext.getCmp('search_s_date_to').getValue(), 'Y-m-d');
                
                window.open(homePath+'/public/hra/attendance/getstatistics/option/csv/key/'+key+'/employment_type/'+employment_type+'/date_from/'+date_from+'/date_to/'+date_to);
            }
        }]
    }],
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: '用工形式',
        locked: true,
        align: 'center',
        dataIndex: 'employment_type',
        width: 80
    }, {
        text: '工号',
        locked: true,
        align: 'center',
        dataIndex: 'number',
        width: 80
    }, {
        text: '中文名',
        locked: true,
        dataIndex: 'cname',
        align: 'center',
        width: 80
    }, {
        text: '英文名',
        dataIndex: 'ename',
        width: 120
    }, {
        text: '部门',
        dataIndex: 'dept_name',
        width: 100
    }, {
        text: '职位',
        dataIndex: 'post_name',
        width: 100
    }, {
        text: '状态',
        align: 'center',
        dataIndex: 'active',
        renderer: function(val, meta){
            if(val == 1){
                return '在职';
            }else{
                meta.style = 'background-color: #F0F0F0';
                return '离职';
            }
        },
        width: 60
    }, {
        text: '考勤 { 出勤 } [天]',
        renderer: function(val, meta, record){
        	if(record.get('attendance_qty') < record.get('workday_qty')){
        		meta.style = 'color: #FF0000; font-weight: bold';
        	}
        	
        	return record.get('workday_qty') + ' { '+record.get('attendance_qty')+' }';
        },
        width: 120
    }, {
        text: '考勤 { 出勤 } [小时]',
        renderer: function(val, meta, record){
        	if(record.get('attendance_hour_qty') < record.get('workhour_qty')){
        		meta.style = 'color: #FF0000; font-weight: bold';
        	}
        	
        	return record.get('workhour_qty') + ' { '+record.get('attendance_hour_qty')+' }';
        },
        width: 140
    }, {
        text: '法定假日 [天]',
        align: 'center',
        dataIndex: 'holiday_qty',
        width: 100
    }, {
        text: '请假 [天]',
        columns: [{
        	text: '事假',
            align: 'center',
            dataIndex: 'v_personal_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '年假',
            align: 'center',
            dataIndex: 'v_vacation_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '病假',
            align: 'center',
            dataIndex: 'v_sick_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '婚假',
            align: 'center',
            dataIndex: 'v_marriage_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '丧假',
            align: 'center',
            dataIndex: 'v_funeral_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '产假',
            align: 'center',
            dataIndex: 'v_childbirth_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '陪产假',
            align: 'center',
            dataIndex: 'v_childbirth_with_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '调休',
            align: 'center',
            dataIndex: 'v_other_qty',
            renderer: qtyRenderer,
            width: 80
        }]
    }, {
        text: '加班 [天]',
        columns: [{
        	text: '工作日',
            align: 'center',
            dataIndex: 'o_workday_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '休息日',
            align: 'center',
            dataIndex: 'o_restday_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '法定假日',
            align: 'center',
            dataIndex: 'o_holiday_qty',
            renderer: qtyRenderer,
            width: 100
        }]
    }, {
        text: '其它[天]',
        columns: [{
        	text: '迟到',
            align: 'center',
            dataIndex: 'late_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '早退',
            align: 'center',
            dataIndex: 'leave_early_qty',
            renderer: qtyRenderer,
            width: 80
        }, {
        	text: '旷工半天',
            align: 'center',
            dataIndex: 'absence_halfday_qty',
            renderer: qtyRenderer,
            width: 100
        }, {
        	text: '旷工一天',
            align: 'center',
            dataIndex: 'absence_qty',
            renderer: qtyRenderer,
            width: 100
        }]
    }],
    bbar: [Ext.create('Ext.PagingToolbar', {
    	border: 0,
        store: statisticsStore,
        displayInfo: true,
        displayMsg: '显示 {0} - {1} 共 {2}',
        emptyMsg: "没有数据"
    })]
});