Ext.define('Type', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "req_flow_id",type: "int"},
             {name: "order_flow_id",type: "int"},
             {name: "tpl_id",type: "int"},
             {name: "active"},
             {name: "chk_package_qty"},
             {name: "code"},
             {name: "name"},
             {name: "description"},
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var typeStore = Ext.create('Ext.data.Store', {
    model: 'Type',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/gettype/option/data'
    }
});

var typeListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/gettype/option/list'
    },
    autoLoad: true
});

var typeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var typeRender = function(val){
	if(val > 0){
		index = typeListStore.findExact('id',val); 
        if (index != -1){
            rs = typeListStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
	}else{
		return '无';
	}
}

// 采购类别管理窗口
var typeWin = Ext.create('Ext.window.Window', {
 title: '采购类别管理',
 height: 300,
 width: 900,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){typeStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     border: 0,
     id: 'typeGrid',
     columnLines: true,
     store: typeStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 typeRowEditing.cancelEdit();
             
             var r = Ext.create('Type', {
                 active: true,
                 chk_package_qty: true,
                 req_flow_id: 0,
                 order_flow_id: 0,
                 tpl_id: 0
             });
             
             typeStore.insert(0, r);
             typeRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('typeGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 typeStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = typeStore.getUpdatedRecords();
             var insertRecords = typeStore.getNewRecords();
             var deleteRecords = typeStore.getRemovedRecords();

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
                     
                     if(data['name'] == '' || data['code'] == '' || data['req_flow_id'] == undefined || data['order_flow_id'] == undefined){
                         valueCheck = false;
                         break;
                     }
                     
                     changeRows.updated.push(data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     var data = insertRecords[i].data;
                     
                     if(data['name'] == '' || data['code'] == '' || data['req_flow_id'] == undefined || data['order_flow_id'] == undefined){
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
                                 url: homePath+'/public/erp/purchse_req/edittype',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         typeStore.reload();
                                         typeListStore.reload();
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
                     Ext.MessageBox.alert('错误', '类别信息不完整(名称、代码、审核流程不能为空)，请继续填写！');
                 }
             }else{
                 Ext.MessageBox.alert('提示', '没有修改任何数据！');
             }
         }
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 typeStore.reload();
         }
     }],
     plugins: typeRowEditing,
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
         xtype: 'checkcolumn',
         text: '检查最小包装量',
         dataIndex: 'chk_package_qty',
         stopSelection: false,
         width: 110
     }, {
         text: '代码',
         align: 'center',
         dataIndex: 'code',
         editor: 'textfield',
         flex: 0.5
     }, {
         text: '名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 1.5
     }, {
         text: '订单模板',
         dataIndex: 'tpl_id',
         renderer: tplRender,
         editor: new Ext.form.field.ComboBox({
             editable: false,
             displayField: 'name',
             valueField: 'id',
             triggerAction: 'all',
             lazyRender: true,
             store: tplListStore,
             queryMode: 'local'
         }),
         width: 80
     }, {
         text: '申请流程',
         dataIndex: 'req_flow_id',
         renderer: flowRender,
         editor: new Ext.form.field.ComboBox({
             editable: false,
             displayField: 'name',
             valueField: 'id',
             triggerAction: 'all',
             lazyRender: true,
             store: flowStore,
             queryMode: 'local'
         }),
         flex: 2
     }, {
         text: '订单流程',
         dataIndex: 'order_flow_id',
         renderer: flowRender,
         editor: new Ext.form.field.ComboBox({
             editable: false,
             displayField: 'name',
             valueField: 'id',
             triggerAction: 'all',
             lazyRender: true,
             store: flowStore,
             queryMode: 'local'
         }),
         flex: 2
     }, {
         text: '描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 2
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