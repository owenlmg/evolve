Ext.define('Separation', {
    extend: 'Ext.data.Model',
    fields: [{name: "month"}, 
             {name: "start_qty", type: 'int'}, 
             {name: "in_qty", type: 'int'}, 
             {name: "out_qty", type: 'int'}, 
             {name: "end_qty", type: 'int'}, 
             {name: "rate", type: 'float'}, 
             {name: "f_start_qty", type: 'int'}, 
             {name: "f_in_qty", type: 'int'}, 
             {name: "f_out_qty", type: 'int'}, 
             {name: "f_end_qty", type: 'int'}, 
             {name: "f_rate", type: 'float'}]
});

var seperationStore = Ext.create('Ext.data.Store', {
    model: 'Separation',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/hra/employee/getseparationrate'
    }
});

// 离职率窗口
var seperationWin = Ext.create('Ext.window.Window', {
 title: '员工离职率',
 height: 400,
 width: 1000,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){seperationStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     border: 0,
     id: 'postGrid',
     columnLines: true,
     store: postStore,
     defaults: {
         align: 'center'
     },
     columns: [{
         xtype: 'rownumberer'
     }, {
         text: '月份',
         dataIndex: 'month',
         flex: 0.5
     }, {
         text: '弹性',
         columns: [{
             text: '月初人数',
             dataIndex: 'start_qty',
             flex: 1
         }, {
             text: '入职人数',
             dataIndex: 'in_qty',
             flex: 1
         }, {
             text: '离职人数',
             dataIndex: 'out_qty',
             flex: 1
         }, {
             text: '期末人数',
             dataIndex: 'end_qty',
             flex: 1
         }, {
             text: '离职率',
             dataIndex: 'rate',
             flex: 1
         }]
     }, {
         text: '非弹性',
         columns: [{
             text: '月初人数',
             dataIndex: 'f_start_qty',
             flex: 1
         }, {
             text: '入职人数',
             dataIndex: 'f_in_qty',
             flex: 1
         }, {
             text: '离职人数',
             dataIndex: 'f_out_qty',
             flex: 1
         }, {
             text: '期末人数',
             dataIndex: 'f_end_qty',
             flex: 1
         }, {
             text: '离职率',
             dataIndex: 'f_rate',
             flex: 1
         }]
     }]
 }]
});