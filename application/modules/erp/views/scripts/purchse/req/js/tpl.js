Ext.define('Tpl', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "active"},
             {name: "type"},
             {name: "name"},
             {name: "description"},
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var tplStore = Ext.create('Ext.data.Store', {
    model: 'Tpl',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/gettpl/option/data'
    }
});

var tplListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/gettpl/type/req/option/list'
    },
    autoLoad: true
});

var tplTypeStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/gettpltype'
    }
});

var tplRender = function(val){
	if(val > 0){
		index = tplListStore.findExact('id',val); 
        if (index != -1){
            rs = tplListStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
	}else{
		return '无';
	}
}

// 模板管理窗口
var tplWin = Ext.create('Ext.window.Window', {
	title: '模板管理',
	height: 300,
	width: 800,
	modal: true,
	constrain: true,
	closeAction: 'hide',
	layout: 'fit',
	tools: [{
		type: 'refresh',
		tooltip: 'Refresh',
		scope: this,
		handler: function(){tplStore.reload();}
	}],
	items: [{
     xtype: 'gridpanel',
     border: 0,
     id: 'tplGrid',
     columnLines: true,
     store: tplStore,
     tbar: [{
         text: '添加',
         disabled: true,
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 
         }
     }, {
         text: '删除',
         disabled: true,
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('typeGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 tplStore.reload();
         }
     }],
     viewConfig: {
         stripeRows: false,// 取消偶数行背景色
         getRowClass: function(record) {
             if(!record.get('active')){
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
         text: '启用',
         align: 'center',
         dataIndex: 'active',
         renderer: function(value, metaData, record, colIndex, store, view) {
        	 if(value == 1){
        		 return '<img src="'+homePath+'/public/images/icons/ok.png"></img>';
        	 }else{
        		 return '<img src="'+homePath+'/public/images/icons/cross.gif"></img>';
        	 }
         },
         flex: 1
     }, {
         text: '类别',
         dataIndex: 'type',
         flex: 1
     }, {
         text: '名称',
         dataIndex: 'name',
         flex: 2
     }, {
         text: '描述',
         dataIndex: 'description',
         flex: 3
     }, {
         text: '备注',
         dataIndex: 'remark',
         flex: 2
     }, {
         text: '创建人',
         hidden: true,
         dataIndex: 'creater',
         flex: 1
     }, {
         text: '创建时间',
         hidden: true,
         dataIndex: 'create_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 2
     }, {
         text: '更新人',
         hidden: true,
         dataIndex: 'updater',
         flex: 1
     }, {
         text: '更新时间',
         hidden: true,
         dataIndex: 'update_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 2
     }]
 }]
});