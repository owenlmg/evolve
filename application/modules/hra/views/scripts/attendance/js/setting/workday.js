// 工作日数据模型
Ext.define('Workday', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "day"}, 
             {name: "weekday"}, 
             {name: "type"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

//工作日设置数据源
var workdayStore = Ext.create('Ext.data.Store', {
model: 'Workday',
proxy: {
   type: 'ajax',
   reader: 'json',
   url: homePath+'/public/hra/attendance/getworkday/option/list'
}
});

//添加年份数据
iniWorkday = function(btn, text) {
	if(btn == 'ok'){
		Ext.Msg.wait('提交中，请稍后...', '提示');
     Ext.Ajax.request({
         url: homePath+'/public/hra/attendance/iniworkday/year/' + text,
         method: 'POST',
         success: function(response, options) {
         	var data = Ext.JSON.decode(response.responseText);

             if(data.success){
                 Ext.MessageBox.alert('提示', data.info);
                 postStore.reload();
                 postListStore.reload();
             }else{
                 Ext.MessageBox.alert('错误', data.info);
             }
         },
         failure: function(response){
             Ext.MessageBox.alert('错误', '初始化失败，请稍后重试');
         }
     });
	}
};

//工作日管理窗口
var workdayWin = Ext.create('Ext.window.Window', {
 title: '工作日管理',
 border: 0,
 height: 400,
 width: 800,
 modal: true,
 constrain: true,
 layout: 'border',
 closeAction: 'hide',
 maximizable: true,
 tools: [{
     type: 'refresh',
     tooltip: '刷新列表',
     scope: this,
     handler: function(){workdayStore.reload();}
 }],
 items: [{
     region: 'center',
     xtype: 'gridpanel',
     columnLines: true,
     store: workdayStore,
     tbar: [{
         xtype: 'combobox',
         id: 'workday_type',
         emptyText: '日期类别...',
         displayField: 'name',
         valueField: 'id',
         width: 100,
         editable: false,
         store: Ext.create('Ext.data.Store', {
             fields: ['name', 'id'],
             data: [
                 {"name": "工作日", "id": 1},
                 {"name": "休息日", "id": 2},
                 {"name": "法定假日", "id": 3}
             ]
         }),
         multiSelect: true
     }, {
         xtype: 'datefield',
         editable: false,
         format: 'Y-m-d',
         width: 120,
         id: 'workday_date_from',
         emptyText: '日期从...',
         value: Ext.util.Format.date(new Date(), 'Y-m-01')
     }, {
         xtype: 'datefield',
         editable: false,
         format: 'Y-m-d',
         width: 120,
         id: 'workday_date_to',
         emptyText: '日期至...',
         value: Ext.util.Format.date(new Date(), 'Y-m-t')
     }, {
         text: '查询',
         iconCls: 'icon-search',
         handler: function(){
             var type = Ext.getCmp('workday_type').getValue();
             var date_from = Ext.Date.format(Ext.getCmp('workday_date_from').getValue(), 'Y-m-d');
             var date_to = Ext.Date.format(Ext.getCmp('workday_date_to').getValue(), 'Y-m-d');
             
             workdayStore.load({
                 params: {
                     type: type,
                     date_from: date_from,
                     date_to: date_to
                 }
             });
         }
     }, {
         text: '添加年份数据',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
         	Ext.MessageBox.prompt('添加年份数据', '请输入年份', iniWorkday);
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = workdayStore.getUpdatedRecords();

             // 判断是否有修改数据
             if(updateRecords.length > 0){
                 var changeRows = {
                         updated:    []
                 }

                 for(var i = 0; i < updateRecords.length; i++){
                     changeRows.updated.push(updateRecords[i].data)
                 }
                 
                 Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                     if(button == 'yes'){
                         var json = Ext.JSON.encode(changeRows);
                         
                         Ext.Msg.wait('提交中，请稍后...', '提示');
                         Ext.Ajax.request({
                             url: homePath+'/public/hra/attendance/editworkday',
                             params: {json: json},
                             method: 'POST',
                             success: function(response, options) {
                                 var data = Ext.JSON.decode(response.responseText);

                                 if(data.success){
                                     Ext.MessageBox.alert('提示', data.info);
                                     workdayStore.reload();
                                     attendanceStore.reload();
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
     plugins: Ext.create('Ext.grid.plugin.CellEditing', {
         clicksToEdit: 1
     }),
     columns: [{
         xtype: 'rownumberer'
     }, {
         text: '日期',
         align: 'center',
         dataIndex: 'day',
         flex: 1
     }, {
         text: '类别',
         dataIndex: 'type',
         align: 'center',
         renderer: function(val, meta){
             if(val == 1){
                 meta.style = 'background-color: #ADFF2F';
                 return '工作日';
             }else if(val == 2){
                 meta.style = 'background-color: #FF8C00';
                 return '休息日';
             }else{
                 meta.style = 'background-color: #FF0000';
                 return '法定假日';
             }
         },
         editor: new Ext.form.field.ComboBox({
             typeAhead: true,
             editable: false,
             triggerAction: 'all',
             displayField: 'text',
             valueField: 'val',
             store: Ext.create('Ext.data.Store', {
                 fields: ['text', 'val'],
                 data: [
                     {"text": "工作日", "val": 1},
                     {"text": "休息日", "val": 2},
                     {"text": "法定假日", "val": 3}
                 ]
             })
         }),
         flex: 1
     }, {
         text: '星期',
         dataIndex: 'weekday',
         align: 'center',
         renderer: function(val, meta){
             if(val == 1){
                 return '一';
             }else if(val == 2){
                 return '二';
             }else if(val == 3){
                 return '三';
             }else if(val == 4){
                 return '四';
             }else if(val == 5){
                 return '五';
             }else if(val == 6){
                 meta.style = 'background-color: #EEEEEE';
                 return '<b>六</b>';
             }else{
                 meta.style = 'background-color: #EEEEEE';
                 return '<b>日</b>';
             }
         },
         flex: 0.5
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 2
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