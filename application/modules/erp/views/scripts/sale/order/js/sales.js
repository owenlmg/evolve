Ext.define('Sales', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "active"},
             {name: "user_id",type: "int"},
             {name: "tel"},
             {name: "fax"},
             {name: "type"},//---------------
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var salesStore = Ext.create('Ext.data.Store', {
    model: 'Sales',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_order/getsales/option/data'
    }
});

var salesListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_order/getsales/option/list'
    },
    autoLoad: true
});

var salesRender = function(val){
	if(val > 0){
		index = salesListStore.findExact('id',val); 
        if (index != -1){
            rs = salesListStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
	}else{
		return '无';
	}
}

// 用户列表
var userListStore = Ext.create('Ext.data.ArrayStore', {
	model: 'Selection',
    proxy: {
        type: 'ajax',
        url: homePath+'/public/admin/account/getuserlist',
        reader: 'json'
    },
    autoLoad: true
});

// 物料类别列表
var saleTypeListStore = Ext.create('Ext.data.ArrayStore', {
	model: 'Selection',
    proxy: {
        type: 'ajax',
        url: homePath+'/public/erp/sale_order/getsaletypelist',
        reader: 'json'
    },
    autoLoad: true
});

var salesRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var saleTypeListRender = function(val, cellMeta, record){
	if(val.length > 0){
		var name = '';
		
		for(var i = 0; i < val.length; i++){
			index = saleTypeListStore.findExact('id',val[i]);
			
			if (index != -1){
	            rs = saleTypeListStore.getAt(index).data; 
	            if(name == ''){
	            	name = rs.name; 
	            }else{
	            	name += ', ' + rs.name; 
	            }
	        }
		}
		
		cellMeta.tdAttr = 'data-qtip="' + name + '"';
		
		return name;
	}else{
		return '无';
	}
}

var userRender = function(val){
	if(val > 0){
		index = userListStore.findExact('id',val); 
        if (index != -1){
            rs = userListStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
	}else{
		return '无';
	}
}

Ext.define('comboGrid', {
    extend: 'Ext.form.ComboBox',
    requires: ['Ext.grid.Panel'],
    alias: ['widget.comboGrid'],
    createPicker: function() {
        var me = this,
        picker,
        menuCls = Ext.baseCSSPrefix + 'menu',
        opts = Ext.apply({
            selModel: {
                mode: me.multiSelect ? 'SIMPLE' : 'SINGLE'
            },
            floating: true,
            hidden: true,
            ownerCt: me.ownerCt,
            cls: me.el.up('.' + menuCls) ? menuCls : '',
            store: me.store,
            displayField: me.displayField,
            focusOnToFront: false,
            pageSize: me.pageSize
        }, me.listConfig, me.defaultListConfig);

    picker = me.picker = Ext.create('Ext.grid.Panel', opts);
    picker.getNode = function() {
        picker.getView().getNode(arguments);
    };

        me.mon(picker, {
            itemclick: me.onItemClick,
            refresh: me.onListRefresh,
            scope: me
        });

        me.mon(picker.getSelectionModel(), {
            selectionChange: me.onListSelectionChange,
            scope: me
        });

        return picker;
    }
});

// 销售员管理窗口
var salesWin = Ext.create('Ext.window.Window', {
 title: '销售员管理',
 height: 300,
 width: 1000,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){salesStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     border: 0,
     id: 'salesGrid',
     columnLines: true,
     store: salesStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 salesRowEditing.cancelEdit();
             
             var r = Ext.create('Sales', {
                 active: true,
                 user_id: 0
             });
             
             salesStore.insert(0, r);
             salesRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('salesGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 salesStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = salesStore.getUpdatedRecords();
             var insertRecords = salesStore.getNewRecords();
             var deleteRecords = salesStore.getRemovedRecords();

             // 判断是否有修改数据
             if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                 var changeRows = {
                         updated:    [],
                         inserted:   [],
                         deleted:    []
                 }

                 // 判断职位信息是否完整
                 var valueCheck = true;

                 for(var i = 0; i < updateRecords.length; i++){
                     var data = updateRecords[i].data;
                     
                     if(data['user_id'] == '' || data['tel'] == '' || data['fax'] == ''){
                         valueCheck = false;
                         break;
                     }
                     
                     changeRows.updated.push(data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     var data = insertRecords[i].data;
                     
                     if(data['user_id'] == '' || data['tel'] == '' || data['fax'] == ''){
                         valueCheck = false;
                         break;
                     }
                     
                     changeRows.inserted.push(data)
                 }
                 
                 for(var i = 0; i < deleteRecords.length; i++){
                     changeRows.deleted.push(deleteRecords[i].data)
                 }

                 // 格式正确则提交修改数据
                 if(valueCheck){
                     Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                         if(button == 'yes'){
                             var json = Ext.JSON.encode(changeRows);
                             
                             Ext.Msg.wait('提交中，请稍后...', '提示');
                             Ext.Ajax.request({
                                 url: homePath+'/public/erp/sale_order/editsales',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         salesStore.reload();
                                         salesListStore.reload();
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
                     Ext.MessageBox.alert('错误', '类别信息不完整(人员、电话、传真不能为空)，请继续填写！');
                 }
             }else{
                 Ext.MessageBox.alert('提示', '没有修改任何数据！');
             }
         }
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 salesStore.reload();
         }
     }],
     plugins: salesRowEditing,
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
         xtype: 'checkcolumn',
         text: '启用',
         dataIndex: 'active',
         stopSelection: false,
         flex: 0.5
     }, {
         text: '销售员',
         dataIndex: 'user_id',
         renderer: userRender,
         editor: new Ext.form.field.ComboBox({
             editable: false,
             displayField: 'name',
             valueField: 'id',
             triggerAction: 'all',
             lazyRender: true,
             store: userListStore,
             queryMode: 'local'
         }),
         width: 120
     }, {
         text: '电话',
         align: 'center',
         dataIndex: 'tel',
         editor: 'textfield',
         flex: 2
     }, {
         text: '传真',
         hidden: true,
         dataIndex: 'fax',
         editor: 'textfield',
         flex: 2
     }, {
    	 text: '销售类别',
         hidden: true,
    	 dataIndex: 'type',
    	 renderer: saleTypeListRender,
    	 editor: new comboGrid({
             editable: false,
             valueField: 'id',
             displayField: 'name',
             multiSelect: true,
             store: saleTypeListStore,
             listConfig: {
            	 layout: 'fit',
            	 columns: [
                    {header: '代码',flex: 1,dataIndex: 'code'},
                    {header: '名称',flex: 1,dataIndex: 'name'}
                 ]
             }
         }),
         flex: 6
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 2
     }, {
         text: '创建人',
         hidden: true,
         dataIndex: 'creater',
         flex: 0.5
     }, {
         text: '创建时间',
         hidden: true,
         dataIndex: 'create_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 1.2
     }, {
         text: '更新人',
         hidden: true,
         dataIndex: 'updater',
         flex: 0.5
     }, {
         text: '更新时间',
         hidden: true,
         dataIndex: 'update_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 1.2
     }]
 }]
});