Ext.define('Ladderqty', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "pricelist_id"},
             {name: "qty"},
             {name: "price",type: "float"},
             {name: "currency"},
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var ladderqtyStore = Ext.create('Ext.data.Store', {
    model: 'Ladderqty',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/warehouse_pricelist/getladderqty/option/data'
    }
});

var ladderqtyRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
            if(editDisable){
                return false;
            }
        }
    }
});

// 阶梯价管理窗口
var ladderqtyWin = Ext.create('Ext.window.Window', {
 title: '阶梯价管理',
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
     handler: function(){ladderqtyStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'ladderqtyGrid',
     columnLines: true,
     store: ladderqtyStore,
     selType: 'checkboxmodel',
     tbar: [{
    	 xtype: 'hiddenfield',
    	 id: 'pricelist_id_ladderqty'
     }, {
         text: '添加阶梯价',
         disabled: editDisable,
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 ladderqtyRowEditing.cancelEdit();
             
             var r = Ext.create('Ladderqty', {
                 qty: 0,
                 price: 0,
                 currency: defaultCurrency
             });
             
             ladderqtyStore.insert(0, r);
             ladderqtyRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除阶梯价',
         disabled: editDisable,
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('ladderqtyGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 ladderqtyStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         disabled: editDisable,
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = ladderqtyStore.getUpdatedRecords();
             var insertRecords = ladderqtyStore.getNewRecords();
             var deleteRecords = ladderqtyStore.getRemovedRecords();

             // 判断是否有修改数据
             if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                 var changeRows = {
                         updated:    [],
                         inserted:   [],
                         deleted:    []
                 }

                 // 判断信息是否完整
                 var valueCheck = true;

                 for(var i = 0; i < updateRecords.length; i++){
                     var data = updateRecords[i].data;
                     
                     if(data['date'] == ''){
                         valueCheck = false;
                         break;
                     }
                     
                     changeRows.updated.push(data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     var data = insertRecords[i].data;
                     
                     if(data['date'] == ''){
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
                                 url: homePath+'/public/erp/warehouse_pricelist/editladderqty',
                                 params: {json: json, ladder_id: Ext.getCmp('pricelist_id_ladderqty').value},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         ladderqtyStore.reload();
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
                     Ext.MessageBox.alert('错误', '信息不完整，请继续填写！');
                 }
             }else{
                 Ext.MessageBox.alert('提示', '没有修改任何数据！');
             }
         }
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 ladderqtyStore.reload();
         }
     }],
     plugins: ladderqtyRowEditing,
     columns: [{
         xtype: 'rownumberer'
     }, {
         text: 'ID',
         dataIndex: 'id',
         hidden: true,
         flex: 1
     }, {
         text: '数量（最小值）',
         dataIndex: 'qty',
         editor: 'numberfield',
         flex: 2
     }, {
         text: '价格',
         dataIndex: 'price',
         editor: new Ext.form.NumberField({  
             decimalPrecision: 8,
             minValue: 0
         }),
         flex: 2
     }, {
         text: '币种 *',
         align: 'center',
         dataIndex: 'currency',
         editor: new Ext.form.field.ComboBox({
             editable: false,
             displayField: 'name',
             valueField: 'text',
             triggerAction: 'all',
             lazyRender: true,
             store: currencyStore,
             queryMode: 'local'
         }),
         width: 70
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 3
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