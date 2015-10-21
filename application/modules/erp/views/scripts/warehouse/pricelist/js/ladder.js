Ext.define('Ladder', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "pricelist_id"},
             {name: "date",type: 'date',dateFormat: 'Y-m-d'},
             {name: "price",type:"float"},
             {name: "qty_range", type: 'int'},
             {name: "currency"},
             {name: "remark"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var ladderStore = Ext.create('Ext.data.Store', {
    model: 'Ladder',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/warehouse_pricelist/getladder/option/data'
    }
});

var ladderRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
            if(editDisable){
                return false;
            }
        }
    }
});

function showLadderqtyWin(id){
	ladderqtyWin.show();
	
	Ext.getCmp('pricelist_id_ladderqty').setValue(id);
	
	ladderqtyStore.load({
		params: {
			ladder_id: id
		}
	});
}

// 阶梯价管理窗口
var ladderWin = Ext.create('Ext.window.Window', {
 title: '价格期间',
 border: 0,
 height: 360,
 width: 660,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){ladderStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'ladderGrid',
     columnLines: true,
     store: ladderStore,
     selType: 'checkboxmodel',
     tbar: [{
    	 xtype: 'hiddenfield',
    	 id: 'pricelist_id_ladder'
     }, {
         text: '添加',
         disabled: editDisable,
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 ladderRowEditing.cancelEdit();
             
             var r = Ext.create('Ladder', {
                 date: Ext.util.Format.date(new Date(), 'Y-m-d'),
                 price: 0,
                 currency: defaultCurrency
             });
             
             ladderStore.insert(0, r);
             ladderRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         disabled: editDisable,
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('ladderGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 ladderStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存',
         disabled: editDisable,
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = ladderStore.getUpdatedRecords();
             var insertRecords = ladderStore.getNewRecords();
             var deleteRecords = ladderStore.getRemovedRecords();

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
                                 url: homePath+'/public/erp/warehouse_pricelist/editladder',
                                 params: {json: json, pricelist_id: Ext.getCmp('pricelist_id_ladder').value},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         ladderStore.reload();
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
     }, {
         text: '数量阶梯价',
         id: 'ladderqtySetBtn',
         disabled: true,
         handler: function(){
     		var selection = Ext.getCmp('ladderGrid').getView().getSelectionModel().getSelection();

         	if(selection.length == 1){
         		if(selection[0].get('id') == undefined){
         			Ext.MessageBox.alert('错误', '请先保存价格期间！');
         		}else{
         			showLadderqtyWin(selection[0].get('id'));
         		}
             }else{
            	 Ext.MessageBox.alert('错误', '请选择价格期间！');
             }
         }
     }, '->', {
         text: '刷新',
         iconCls: 'icon-refresh',
         handler: function(){
        	 ladderStore.reload();
         }
     }],
     plugins: ladderRowEditing,
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
         text: '阶梯价',
         align: 'center',
         dataIndex: 'qty_range',
         renderer: function(value, metaData, record, colIndex, store, view) {
        	 if(value == 1){
                 var id = record.get('id');
                 return '<div style="cursor:pointer;" onclick="showLadderqtyWin('+id+');"><img src="'+homePath+'/public/images/icons/ok.png"></img></div>';
             }
         },
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
     }],
     listeners: {
    	 selectionchange: function( sel, selected, eOpts ){
         	if(selected.length > 0){
         		Ext.getCmp('ladderqtySetBtn').enable();
         	}else{
         		Ext.getCmp('ladderqtySetBtn').disable();
             }
         }
     }
 }]
});