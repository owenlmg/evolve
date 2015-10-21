Ext.define('Type', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
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
        url: homePath+'/public/erp/warehouse_index/gettype/option/data'
    }
});

var typeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 仓库类别
var typeListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
       type: 'ajax',
       reader: 'json',
       url: homePath+'/public/erp/warehouse_index/gettype/option/list'
    },
    autoLoad: true
});

var warehouseTypeRender = function (val){
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
};

// 仓库类别管理窗口
var typeWin = Ext.create('Ext.window.Window', {
 title: '仓库管理',
 height: 300,
 width: 700,
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
                 active: true
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
         text: '保存',
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

                 // 判断仓库信息是否完整
                 var valueCheck = true;

                 for(var i = 0; i < updateRecords.length; i++){
                     var data = updateRecords[i].data;
                     
                     if(data['name'] == ''){
                         valueCheck = false;
                         break;
                     }
                     
                     changeRows.updated.push(data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     var data = insertRecords[i].data;
                     
                     if(data['name'] == ''){
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
                                 url: homePath+'/public/erp/warehouse_index/edittype',
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
                     Ext.MessageBox.alert('错误', '仓库信息不完整，请继续填写！');
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
         flex: 1
     }, {
         xtype: 'checkcolumn',
         text: '启用',
         dataIndex: 'active',
         stopSelection: false,
         flex: 1
     }, {
         text: '仓库代码',
         dataIndex: 'code',
         editor: 'textfield',
         flex: 2
     }, {
         text: '仓库名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 2
     }, {
         text: '仓库描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 3
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
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
         flex: 1.5
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
         flex: 1.5
     }]
 }]
});