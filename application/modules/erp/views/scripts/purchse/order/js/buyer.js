Ext.define('Buyer', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "active"},
             {name: "user_id",type: "int"},
             {name: "tel"},
             {name: "fax"},
             {name: "type"},
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var buyerStore = Ext.create('Ext.data.Store', {
    model: 'Buyer',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_order/getbuyer/option/data'
    }
});

var buyerListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_order/getbuyer/option/list'
    },
    autoLoad: true
});

var buyerRender = function(val){
	if(val > 0){
		index = buyerListStore.findExact('id',val); 
        if (index != -1){
            rs = buyerListStore.getAt(index).data; 
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
var materialTypeListStore = Ext.create('Ext.data.ArrayStore', {
	model: 'Selection',
    proxy: {
        type: 'ajax',
        url: homePath+'/public/erp/purchse_order/getmaterialtypelist',
        reader: 'json'
    },
    autoLoad: true
});

var buyerRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var materialTypeListRender = function(val, cellMeta, record){
	if(val.length > 0){
		var name = '';
		
		for(var i = 0; i < val.length; i++){
			index = materialTypeListStore.findExact('id',val[i]);
			
			if (index != -1){
	            rs = materialTypeListStore.getAt(index).data; 
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
    // copied from ComboBox 
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

    // NOTE: we simply use a grid panel
    //picker = me.picker = Ext.create('Ext.view.BoundList', opts);
    picker = me.picker = Ext.create('Ext.grid.Panel', opts);
    // hack: pass getNode() to the view
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

// 采购员管理窗口
var buyerWin = Ext.create('Ext.window.Window', {
 title: '采购员管理',
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
     handler: function(){buyerStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     border: 0,
     id: 'buyerGrid',
     columnLines: true,
     store: buyerStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 buyerRowEditing.cancelEdit();
             
             var r = Ext.create('Buyer', {
                 active: true,
                 user_id: 0
             });
             
             buyerStore.insert(0, r);
             buyerRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('buyerGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 buyerStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = buyerStore.getUpdatedRecords();
             var insertRecords = buyerStore.getNewRecords();
             var deleteRecords = buyerStore.getRemovedRecords();

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
                                 url: homePath+'/public/erp/purchse_order/editbuyer',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         buyerStore.reload();
                                         buyerListStore.reload();
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
        	 buyerStore.reload();
         }
     }],
     plugins: buyerRowEditing,
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
         text: '采购员',
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
         dataIndex: 'fax',
         editor: 'textfield',
         flex: 2
     }, {
    	 text: '采购物料类别',
    	 dataIndex: 'type',
    	 renderer: materialTypeListRender,
    	 editor: new comboGrid({
             editable: false,
             valueField: 'id',
             displayField: 'name',
             multiSelect: true,
             store: materialTypeListStore,
             listConfig: {
            	 layout: 'fit',
            	 columns: [
                    {header: '大类',flex: 1,dataIndex: 'code'},
                    {header: '小类',flex: 1,dataIndex: 'name'}
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