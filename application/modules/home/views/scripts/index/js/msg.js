/**
 * 我的消息
 */
Ext.define('msg', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: 'id'}, 
             {name: 'view'}, 
             {name: 'email'}, 
             {name: 'title'}, 
             {name: 'priority'}, 
             {name: 'content'}, 
             {name: 'remark'}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

var msgStore = Ext.create('Ext.data.Store', {
    model: 'msg',
    pageSize: 5,
    remoteSort: true,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/user/msg/getlist/type/shortlist'
    },
    autoLoad: true
});

var msgListStore = Ext.create('Ext.data.Store', {
    model: 'msg',
    pageSize: 20,
    remoteSort: true,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/user/msg/getlist/type/list'
    },
    listeners: {
    	beforeload: function(){
    		var key = Ext.getCmp('search_key').getValue();
            var date_from = Ext.Date.format(Ext.getCmp('search_date_from').getValue(), 'Y-m-d');
            var date_to = Ext.Date.format(Ext.getCmp('search_date_to').getValue(), 'Y-m-d');
    		
    		Ext.apply(msgListStore.proxy.extraParams, {
    			key: key,
    			date_from: date_from,
    			date_to: date_to
            });
        }
    }
});

// 渲染未读图标
var viewRender = function(val){
	if(val){
		return '<img src="' + homePath + '/public/images/icons/email_open.png"></img>';
	}else{
		return '<img src="' + homePath + '/public/images/icons/email.png"></img>';
	}
};

// 渲染邮件图标
var yesNoRender = function(val){
	if(val){
		return '<img src="' + homePath + '/public/images/icons/ok.png"></img>';
	}else{
		return '<img src="' + homePath + '/public/images/icons/cross.gif"></img>';
	}
};

// 渲染查看图标
var viewMsgRender = function(val, cellmeta, record){
	var emailImg = '';
	
	if(record.data['email']){
		emailImg = '<img src="' + homePath + '/public/images/icons/ok.png"></img>';
	}else{
		emailImg = '<img src="' + homePath + '/public/images/icons/cross.png"></img>';
	}
	
	var content = '<p><b>发送人：</b>'+record.data['creater']+'</p>' + 
				  '<p><b>发送时间：</b>'+Ext.util.Format.date(record.data['create_time'], "Y-m-d H:i:s")+'</p>' + 
				  '<p><b>标题：</b>'+record.data['title']+'</p>' + 
				  '<p><b>优先级：</b>'+record.data['priority']+'</p>' + 
				  '<p><b>邮件：</b>'+record.data['email']+'</p>' + 
				  '<p><b>内容：</b>'+record.data['content']+'</p>' + 
				  '<p><b>备注：</b>'+record.data['remark']+'</p>';
	cellmeta.tdAttr = 'data-qtip="' + content + '"';
	
	return val;
};

var bookTplMarkup = [
	'<div class="comment_head">'+
		'<div class="comment_head_l"><b>标题：</b>{title}&nbsp;&nbsp;<b>邮件：</b>{email}&nbsp;&nbsp;<b>优先级：</b> {priority}</div>'+
		'<div class="comment_head_r"><b>创建人：</b>{creater}<b>&nbsp;&nbsp;创建时间：</b> {create_time}</div>'+
	'</div>',
	'<div class="comment_content" style="height: 80px;overflow:auto;">{content}</div>',
	'<div class="comment_content" style="background-color: #EEEEEE;"><b>备注：</b>{remark}</div>'
];

var bookTpl = Ext.create('Ext.Template', bookTplMarkup);

// 更新消息状态
function updateMsgStatus(id, store){
	Ext.Ajax.request({
        url: homePath + '/public/user/msg/updatestatus',
        params: {id: id},
        method: 'POST',
        success: function(response, options) {
            var data = Ext.JSON.decode(response.responseText);

            if(data.success){
            	store.load();
            }else{
                Ext.MessageBox.alert('错误', data.info);
            }
        },
        failure: function(response){
            Ext.MessageBox.alert('错误', '保存提交失败');
        }
    });
}

// 消息窗口列表
var msgGridPanel = Ext.create('Ext.grid.Panel', {
	id: 'msgPanel',
	forceFit: true,
    height:300,
    split: true,
    region: 'north',
    columnLines: true,
    store: msgListStore,
	tbar: [{
        xtype: 'textfield',
        id: 'search_key',
        width: 150,
        emptyText: '关键字...',
        listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	msgListStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        width: 120,
        id: 'search_date_from',
        emptyText: '日期从...'
    }, {
        xtype: 'datefield',
        format: 'Y-m-d',
        width: 120,
        id: 'search_date_to',
        emptyText: '日期至...'
    }, {
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	if(Ext.getCmp('search_date_from').isValid() && Ext.getCmp('search_date_to').isValid()){
        		msgListStore.loadPage(1);
        	}
        }
    }, '->', {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
        	msgListStore.reload();
        }
    }],
    columns: [{
    	text: 'ID',
    	align: 'center',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'view',
    	align: 'center',
        renderer: viewRender,
        flex: 0.5
    }, {
    	text: '邮件',
    	align: 'center',
        dataIndex: 'email',
        renderer: yesNoRender,
        flex: 1
    }, {
    	text: '优先级',
    	align: 'center',
        dataIndex: 'priority',
        flex: 1
    }, {
        text: '标题',
        flex: 3,
        dataIndex: 'title',
        renderer: function(val, cellmeta, record){
        	cellmeta.tdAttr = 'data-qtip="' + val + '"';
        	return val;
        }
    }, {
        text: '备注',
        flex: 2,
        dataIndex: 'remark',
        renderer: function(val, cellmeta, record){
        	cellmeta.tdAttr = 'data-qtip="' + val + '"';
        	return val;
        }
    }, {
        text: '发送人',
    	align: 'center',
        flex: 1,
        dataIndex: 'creater'
    }, {
        text: '发送时间',
    	align: 'center',
        flex: 2,
        dataIndex: 'create_time',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s')
    }, {
        text: '更新人',
    	align: 'center',
        hidden: true,
        flex: 1,
        dataIndex: 'creater'
    }, {
        text: '更新时间',
    	align: 'center',
        hidden: true,
        flex: 3,
        dataIndex: 'create_time',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s')
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: msgListStore,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: ''
    }),
    listeners: {
    	selectionchange: function(sm, selectedRecord) {
            if (selectedRecord.length) {
                var detailPanel = Ext.getCmp('detailPanel');
                var data = selectedRecord[0].data;
                data['create_time'] = Ext.util.Format.date(data['create_time'], "Y-m-d H:i:s");
                
                detailPanel.update(bookTpl.apply(data));
                
                // 当未读消息时，点击同时更新消息状态
                if(!selectedRecord[0].get('view')){
                	updateMsgStatus(data['id'], msgListStore);
                }
            }
        }
    }
});

// 消息列表窗口
var msgWin = Ext.create('Ext.window.Window', {
	title: '我的消息',
	border: 0,
	maximizable: true,
	height: 500,
	width: 900,
	modal: true,
	constrain: true,
	closeAction: 'hide',
	layout: 'border',
	items: [msgGridPanel, {
        id: 'detailPanel',
        region: 'center',
        bodyStyle: "background: #ffffff;",
        html: '选择消息行查看消息...'
	}]
});

var msgGrid = Ext.create('Ext.grid.Panel', {
	title: '我的消息',
	border: 0,
    store: msgStore,
    columns: [{
    	text: 'ID',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'view',
    	align: 'center',
        renderer: viewRender,
        flex: 1.5
    }, {
    	text: '优先级',
    	align: 'center',
        dataIndex: 'priority',
        flex: 2
    }, {
        text: '标题',
        flex: 4,
        dataIndex: 'title',
        renderer: viewMsgRender
    }, {
        text: '发送人',
    	align: 'center',
        flex: 2,
        dataIndex: 'creater'
    }, {
        text: '发送时间',
    	align: 'center',
        hidden: true,
        flex: 4,
        dataIndex: 'create_time',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s')
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: msgStore,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: '',
        items:[{
           tooltip: '查看详细',
           iconCls: 'icon-grid',
           handler: function(){
        	   openMsgWin();
           }
        }]
    }),
    listeners: {
    	select: function(grid, record, index, eOpts){
    		updateMsgStatus(record.get('id'), msgStore);
    	}
    }
});

function openMsgWin(){
	msgListStore.loadPage(1);
	msgWin.show();
}