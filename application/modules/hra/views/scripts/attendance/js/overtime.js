// 月度考勤统计数据模型
Ext.define('Overtime', {
    extend: 'Ext.data.Model',
    fields: [{name: "number"}, 
             {name: "dept"}, 
             {name: "cname"}, 
             {name: "ename"}, 
             {name: "type"}, 
             {name: "state"}, 
             {name: "dayCnt"}, 
             {name: "legalCnt"}, 
             {name: "personalCnt"}, 
             {name: "sickCnt"}, 
             {name: "annualCnt"}, 
             {name: "twdoCnt"}, 
             {name: "otherCnt"}, 
             {name: "personalCnt"}, 
             {name: "sickCnt"}, 
             {name: "annualCnt"}, 
             {name: "lateCnt"}, 
             {name: "earlyCnt"}]
});

// 月度考勤统计数据源
var overtimeStore = Ext.create('Ext.data.Store', {
    model: 'Overtime',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/hra/attendance/getstmon'
    }
});

//月度考勤统计窗口
var overtimeGrid = Ext.create('Ext.grid.Panel', {
    title: '加班统计',
    border: 0,
    layout: 'fit',
    columnLines: true,
    store: overtimeStore,
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: '类别',
        locked: true,
        align: 'center',
        dataIndex: 'type',
        renderer: function(val, meta){
            if(val == 0){
                meta.style = 'background-color: #00FF00;';
                return '职员';
            }else{
                meta.style = 'background-color: #0000FF';
                return '工人';
            }
        },
        width: 60
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
        align: 'center',
        width: 100
    }, {
        text: '部门',
        dataIndex: 'dept',
        renderer: deptRender,
        width: 80
    }, {
        text: '职位',
        dataIndex: 'dept',
        renderer: postRender,
        width: 80
    }, {
        text: '状态',
        dataIndex: 'state',
        width: 60
    }, {
        text: '考勤/出勤 [天]',
        align: 'center',
        dataIndex: 'dayCnt',
        width: 100
    }, {
        text: '法定假日 [天]',
        dataIndex: 'legalCnt',
        width: 100
    }, {
        text: '请假 [天]',
        columns: [{
        	text: '事假',
            align: 'center',
            dataIndex: 'personalCnt',
            width: 60
        }, {
        	text: '病假',
            align: 'center',
            dataIndex: 'sickCnt',
            width: 60
        }, {
        	text: '年假',
            align: 'center',
            dataIndex: 'annualCnt',
            width: 60
        }, {
        	text: '调休',
            align: 'center',
            dataIndex: 'twdoCnt',
            width: 60
        }, {
        	text: '其它',
            align: 'center',
            dataIndex: 'otherCnt',
            width: 60
        }]
    }, {
        text: '加班 [天]',
        columns: [{
        	text: '工作日',
            align: 'center',
            dataIndex: 'personalCnt',
            width: 60
        }, {
        	text: '休息日',
            align: 'center',
            dataIndex: 'sickCnt',
            width: 60
        }, {
        	text: '法定假日',
            align: 'center',
            dataIndex: 'annualCnt',
            width: 80
        }]
    }, {
        text: '其它[天]',
        columns: [{
        	text: '迟到',
            align: 'center',
            dataIndex: 'lateCnt',
            width: 60
        }, {
        	text: '早退',
            align: 'center',
            dataIndex: 'earlyCnt',
            width: 60
        }]
    }]
});