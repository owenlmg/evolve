// 印章使用记录数据模型
Ext.define('Log', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "seal_name"}, 
             {name: "state"}, 
             {name: "apply_user_name"}, 
             {name: "apply_user"}, 
             {name: "apply_time"}, 
             {name: "apply_reason"}, 
             {name: "review_user_name"}, 
             {name: "review_user"}, 
             {name: "review_time"}, 
             {name: "review_opinion"}, 
             {name: "remark"}]
});

// 印章信息数据源
var logStore = Ext.create('Ext.data.Store', {
    model: 'Log',
    pageSize: 50,
    remoteSort: true,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/hra/seal/getlog'
    },
    listeners: {
    	beforeload: function(){
    		var key = Ext.getCmp('search_key').getValue();
    		var search_date_from = Ext.Date.format(Ext.getCmp('search_date_from').getValue(), 'Y-m-d');
            var search_date_to = Ext.Date.format(Ext.getCmp('search_date_to').getValue(), 'Y-m-d');
            
    		Ext.apply(logStore.proxy.extraParams, {
    			key: key,
    			date_from: search_date_from,
    			date_to: search_date_to
            });
        }
    }
});

function deleteSealApply(id){
    Ext.MessageBox.confirm('确认', '确定删除？', function(button, text){
        if(button == 'yes'){
            Ext.Msg.wait('提交中，请稍后...', '提示');
            Ext.Ajax.request({
                url: homePath+'/public/hra/seal/deletelog/id/'+id,
                params: {},
                method: 'POST',
                success: function(response, options) {
                    var data = Ext.JSON.decode(response.responseText);
    
                    if(data.success){
                        Ext.MessageBox.alert('提示', data.info);
                        logStore.reload(1);
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

var deleteSealApplyRender = function(val, cellmeta, record, colIndex, store, view){
    if((sealAdmin || user_id == record.get('apply_user')) && (record.get('state') == 1 || record.get('state') == 3)){
        return '<img style="cursor:pointer;" src="' + homePath + '/public/images/icons/delete.gif" onclick="deleteSealApply('+record.get('id')+');"></img>';
    }
    //return '<img style="cursor:pointer;" src="' + homePath + '/public/images/icons/delete.gif" onclick="deleteSealApply('+record.get('id')+');"></img>';
};

// 印章使用申请记录窗口
var logWin = Ext.create('Ext.window.Window', {
	title: '印章使用申请记录',
	height: 500,
	width: 1000,
	maximizable: true,
	modal: true,
	constrain: true,
	closeAction: 'hide',
	layout: 'fit',
	tools: [{
	    type: 'refresh',
	    tooltip: 'Refresh',
	    scope: this,
	    handler: function(){logStore.reload();}
	}],
	items: [{
	    xtype: 'gridpanel',
	    border: 0,
	    id: 'logGrid',
	    columnLines: true,
	    store: logStore,
	    selType: 'checkboxmodel',
	    tbar: [{
	        xtype: 'textfield',
	        id: 'search_key',
	        width: 150,
	        emptyText: '关键字...',
	        listeners: {
	        	specialKey :function(field,e){
	                if (e.getKey() == Ext.EventObject.ENTER){
	                	logStore.loadPage(1);
	                }
	            }
	        }
	    }, {
            xtype: 'datefield',
            format: 'Y-m-d',
            width: 120,
            value: currentMonthStart,
            id: 'search_date_from',
            emptyText: '日期从...'
        }, {
            xtype: 'datefield',
            format: 'Y-m-d',
            width: 120,
            value: currentMonthEnd,
            id: 'search_date_to',
            emptyText: '日期至...'
        }, {
	        xtype: 'splitbutton',
	        text: '查询',
	        iconCls: 'icon-search',
	        handler: function(){
	        	logStore.loadPage(1);
	        },
	        menu: [{
	        	text: '导出',
	            iconCls: 'icon-export',
	            handler: function(){
	            	window.open(homePath+'/public/hra/seal/getlog/option/csv');
	            }
	        }]
	    }, '->', {
	        text: '刷新',
	        iconCls: 'icon-refresh',
	        handler: function(){
	        	logStore.reload();
	        }
	    }],
	    viewConfig: {
            stripeRows: false,// 取消偶数行背景色
            getRowClass: function(record) {
                if(record.get('state') == '拒绝'){
                    return 'light-red-row';
                }else if(record.get('state') == '批准'){
                    return 'gray-row';
                }
            }
        },
	    columns: [{
            xtype: 'rownumberer'
        }, {
	        text: 'ID',
	        dataIndex: 'id',
	        hidden: true,
	        flex: 0.5
	    }, {
	        text: '删除',
	        align: 'center',
	        dataIndex: 'delete',
	        renderer: deleteSealApplyRender,
	        flex: 0.5
	    }, {
	    	text: '状态',
	        dataIndex: 'state',
	        flex: 1
	    }, {
	        text: '印章',
	        dataIndex: 'seal_name',
	        flex: 1
	    }, {
	        text: '事由',
	        dataIndex: 'apply_reason',
	        renderer: function(value,metaData,record,colIndex,store,view) {
	            metaData.tdAttr = 'data-qtip="'+value+'"';
	            return value;
	        },
	        flex: 2
	    }, {
	        text: '审核人',
	        dataIndex: 'review_user_name',
	        align: 'center',
	        flex: 1
	    }, {
	        text: '审核时间',
	        dataIndex: 'review_time',
	        align: 'center',
	        flex: 1.8
	    }, {
	        text: '审核意见',
	        dataIndex: 'review_opinion',
	        renderer: function(value,metaData,record,colIndex,store,view) {
	            metaData.tdAttr = 'data-qtip="'+value+'"';
	            return value;
	        },
	        flex: 2
	    }, {
	        text: '申请人',
	        dataIndex: 'apply_user_name',
	        align: 'center',
	        flex: 1
	    }, {
	        text: '申请时间',
	        dataIndex: 'apply_time',
	        align: 'center',
	        flex: 1.8
        }],
        bbar: Ext.create('Ext.PagingToolbar', {
            store: logStore,
            displayInfo: true,
            displayMsg: '显示 {0} - {1} 共 {2}',
            emptyMsg: "没有数据"
        })
    }]
});