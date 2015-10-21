// 数据模型
Ext.define('Param', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "employment_type", type: "int"}, 
             {name: "private", type: "int"}, 
             {name: "vacation", type: "int"}, 
             {name: "sick", type: "int"}, 
             {name: "marriage", type: "int"}, 
             {name: "funeral", type: "int"}, 
             {name: "maternity", type: "int"}, 
             {name: "paternity", type: "int"}, 
             {name: "other", type: "int"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 数据源
var paramStore = Ext.create('Ext.data.Store', {
	model: 'Param',
	proxy: {
	   type: 'ajax',
	   reader: 'json',
	   url: homePath+'/public/hra/attendance/getparam'
	}
});

var paramsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 窗口
var paramWin = Ext.create('Ext.window.Window', {
 title: '参数管理',
 height: 260,
 width: 600,
 modal: true,
 constrain: true,
 layout: 'border',
 closeAction: 'hide',
 maximizable: true,
 tools: [{
     type: 'refresh',
     tooltip: '刷新列表',
     scope: this,
     handler: function(){paramStore.reload();}
 }],
 items: [{
     region: 'center',
     xtype: 'gridpanel',
     border: 0,
     columnLines: true,
     store: paramStore,
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
        	 paramsRowEditing.cancelEdit();
             
             var r = Ext.create('Param', {
                 employee_type: 1,
             });

             paramStore.insert(0, r);
             paramsRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         handler: function(){
         	var selection = this.up('grid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
            	 paramStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
        	 var updateRecords = paramStore.getUpdatedRecords();
     		 var insertRecords = paramStore.getNewRecords();
             var deleteRecords = paramStore.getRemovedRecords();

             // 判断是否有修改数据
             if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
            	 var changeRows = {
                         updated:    [],
                         inserted:   [],
                         deleted:    []
                 }
                 
                 for(var i = 0; i < updateRecords.length; i++){
                     changeRows.updated.push(updateRecords[i].data)
                 }
                 
                 for(var i = 0; i < insertRecords.length; i++){
                     changeRows.inserted.push(insertRecords[i].data)
                 }
                 
                 for(var i = 0; i < deleteRecords.length; i++){
                     changeRows.deleted.push(deleteRecords[i].data)
                 }
                 
                 Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                     if(button == 'yes'){
                         var json = Ext.JSON.encode(changeRows);
                         
                         Ext.Msg.wait('提交中，请稍后...', '提示');
                         Ext.Ajax.request({
                             url: homePath+'/public/hra/attendance/editparam',
                             params: {json: json},
                             method: 'POST',
                             success: function(response, options) {
                                 var data = Ext.JSON.decode(response.responseText);

                                 if(data.success){
                                     Ext.MessageBox.alert('提示', data.info);
                                     paramStore.reload();
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
     }],
     plugins: paramsRowEditing,
     columns: [{
         xtype: 'rownumberer'
     }, {
         text: '用工形式',
         width: 80,
         dataIndex: 'employment_type',
         align: 'center',
         editor: new Ext.form.field.ComboBox({
             typeAhead: true,
             editable: false,
             triggerAction: 'all',
             displayField: 'name',
             valueField: 'id',
             queryMode: 'local',
             store: typeListStore
         }),
         renderer: typeRender
     }, {
         text: '事假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'private',
         editor: {
        	 xtype: 'numberfield',
        	 minValue: 0
         },
         flex: 1
     }, {
         text: '年假(天)',
         align: 'center',
         dataIndex: 'vacation',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '病假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'sick',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '婚假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'marriage',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '丧假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'funeral',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '产假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'maternity',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '陪产假(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'paternity',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '其它(天)',
         align: 'center',
         hidden: true,
         dataIndex: 'other',
         editor: 'numberfield',
         minValue: 0,
         flex: 1
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 3
     }, {
         text: '创建人',
         hidden: true,
         dataIndex: 'creater',
         align: 'center',
         flex: 1
     }, {
         text: '创建时间',
         hidden: true,
         dataIndex: 'create_time',
         align: 'center',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 1.5
     }, {
         text: '更新人',
         hidden: true,
         dataIndex: 'updater',
         align: 'center',
         flex: 1
     }, {
         text: '更新时间',
         hidden: true,
         dataIndex: 'update_time',
         align: 'center',
         renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
         flex: 1.5
     }]
 }]
});