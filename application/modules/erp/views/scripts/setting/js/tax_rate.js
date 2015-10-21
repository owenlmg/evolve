Ext.define('TaxRate', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "date",type: 'date',dateFormat: 'Y-m-d'}, 
             {name: "rate"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

var taxRateStore = Ext.create('Ext.data.Store', {
    model: 'TaxRate',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/setting_tax/gettaxrate/option/data'
    }
});

var taxRateRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 税率管理窗口
var taxRateWin = Ext.create('Ext.window.Window', {
 title: '税率管理',
 border: 0,
 height: 300,
 width: 600,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){taxRateStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'taxRateGrid',
     columnLines: true,
     store: taxRateStore,
     selType: 'checkboxmodel',
     tbar: [{
    	 xtype: 'hiddenfield',
    	 id: 'tax_id_to_rate'
     }, {
         text: '添加税率',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 taxRateRowEditing.cancelEdit();
             
             var r = Ext.create('TaxRate', {
                 date: Ext.util.Format.date(new Date(), 'Y-m-d'),
                 rate: 1
             });
             
             taxRateStore.insert(0, r);
             taxRateRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除税率',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('taxRateGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 taxRateStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = taxRateStore.getUpdatedRecords();
             var insertRecords = taxRateStore.getNewRecords();
             var deleteRecords = taxRateStore.getRemovedRecords();

             // 判断是否有修改数据
             if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                 var changeRows = {
                         updated:    [],
                         inserted:   [],
                         deleted:    []
                 }

                 for(var i = 0; i < updateRecords.length; i++){
                     var data = updateRecords[i].data;

                     changeRows.updated.push(data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     var data = insertRecords[i].data;

                     changeRows.inserted.push(data)
                 }
                 
                 for(var i = 0; i < deleteRecords.length; i++){
                     changeRows.deleted.push(deleteRecords[i].data)
                 }

                 Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                     if(button == 'yes'){
                         var json = Ext.JSON.encode(changeRows);
                         var selection = Ext.getCmp('taxGrid').getView().getSelectionModel().getSelection();
                         
                         
                         Ext.Msg.wait('提交中，请稍后...', '提示');
                         Ext.Ajax.request({
                             url: homePath+'/public/erp/setting_tax/edittaxrate',
                             params: {json: json, tax_id: Ext.getCmp('tax_id_to_rate').value},
                             method: 'POST',
                             success: function(response, options) {
                                 var data = Ext.JSON.decode(response.responseText);

                                 if(data.success){
                                     Ext.MessageBox.alert('提示', data.info);
                                     taxRateStore.reload();
                                     taxStore.reload();
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
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 taxRateStore.reload();
         }
     }],
     plugins: taxRateRowEditing,
     columns: [{
         xtype: 'rownumberer'
     }, {
         text: 'ID',
         dataIndex: 'id',
         hidden: true,
         flex: 1
     }, {
         text: '生效日期',
         dataIndex: 'date',
         renderer: Ext.util.Format.dateRenderer('Y-m-d'),
         editor: {
             xtype: 'datefield',
             editable: false,
             format: 'Y-m-d'
         },
         flex: 3
     }, {
         text: '税率',
         dataIndex: 'rate',
         editor: 'numberfield',
         flex: 2
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 5
     }, {
         text: '创建人',
         hidden: true,
         dataIndex: 'creater',
         flex: 2
     }, {
         text: '创建时间',
         hidden: true,
         dataIndex: 'create_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 3
     }, {
         text: '更新人',
         hidden: true,
         dataIndex: 'updater',
         flex: 2
     }, {
         text: '更新时间',
         hidden: true,
         dataIndex: 'update_time',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 3
     }]
 }]
});