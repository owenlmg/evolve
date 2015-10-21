Ext.define('CurrencyRate', {
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

var currencyRateStore = Ext.create('Ext.data.Store', {
    model: 'CurrencyRate',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/setting_currency/getcurrencyrate/option/data'
    }
});

var currencyRateRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 汇率管理窗口
var currencyRateWin = Ext.create('Ext.window.Window', {
 title: '汇率管理',
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
     handler: function(){currencyRateStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'currencyRateGrid',
     columnLines: true,
     store: currencyRateStore,
     selType: 'checkboxmodel',
     tbar: [{
    	 xtype: 'hiddenfield',
    	 id: 'currency_id_to_rate'
     }, {
         text: '添加汇率',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 currencyRateRowEditing.cancelEdit();
             
             var r = Ext.create('CurrencyRate', {
                 date: Ext.util.Format.date(new Date(), 'Y-m-d'),
                 rate: 1
             });
             
             currencyRateStore.insert(0, r);
             currencyRateRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除汇率',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('currencyRateGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 currencyRateStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = currencyRateStore.getUpdatedRecords();
             var insertRecords = currencyRateStore.getNewRecords();
             var deleteRecords = currencyRateStore.getRemovedRecords();

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
                         var selection = Ext.getCmp('currencyGrid').getView().getSelectionModel().getSelection();
                         
                         
                         Ext.Msg.wait('提交中，请稍后...', '提示');
                         Ext.Ajax.request({
                             url: homePath+'/public/erp/setting_currency/editcurrencyrate',
                             params: {json: json, currency_id: Ext.getCmp('currency_id_to_rate').value},
                             method: 'POST',
                             success: function(response, options) {
                                 var data = Ext.JSON.decode(response.responseText);

                                 if(data.success){
                                     Ext.MessageBox.alert('提示', data.info);
                                     currencyRateStore.reload();
                                     currencyStore.reload();
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
        	 currencyRateStore.reload();
         }
     }],
     plugins: currencyRateRowEditing,
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
         text: '汇率',
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