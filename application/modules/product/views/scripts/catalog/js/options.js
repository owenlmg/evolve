// 产品分类数据模型
Ext.define('Type', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "name"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 产品系列数据模型
Ext.define('Series', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "name"}, 
             {name: "code"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 产品阶段数据模型
Ext.define('Stage', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "name"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 产品开发模式数据模型
Ext.define('Mode', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "name"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 流程列表数据源
var flowStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/admin/flow/getflowforcombo'
    },
    autoLoad: true
});

// 产品分类数据源
var typeStore = Ext.create('Ext.data.Store', {
    model: 'Type',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/product/catalog/gettype'
    },
    autoLoad: true
});

// 产品系列数据源
var seriesStore = Ext.create('Ext.data.Store', {
    model: 'Series',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/product/catalog/getseries'
    },
    autoLoad: true
});

// 产品阶段数据源
var stageStore = Ext.create('Ext.data.Store', {
    model: 'Stage',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/product/catalog/getstage'
    },
    autoLoad: true
});

// 产品开发模式数据源
var modeStore = Ext.create('Ext.data.Store', {
    model: 'Mode',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/product/catalog/getmode'
    },
    autoLoad: true
});

var stageRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var seriesRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var typeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var modeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 阶段渲染
var stageRender = function (val, meta){
    if(val > 0){
        index = stageStore.findExact('id',val); 
        if (index != -1){
            rs = stageStore.getAt(index).data; 
            
            if(fs.name == '生命周期终结(EOL)'){
            	meta.style = 'background-color: #FF4500';
            }
            
            return rs.name; 
        }
        return val;
    }else{
        return '无';
    }
};

// 产品系列渲染
var seriesRender = function (val){
    if(val > 0){
        index = seriesStore.findExact('id',val); 
        if (index != -1){
            rs = seriesStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
    }else{
        return '无';
    }
};

// 产品分类渲染
var typeRender = function (val){
    if(val > 0){
        index = typeStore.findExact('id',val); 
        if (index != -1){
            rs = typeStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
    }else{
        return '无';
    }
};

// 产品开发模式渲染
var modeRender = function (val){
    if(val > 0){
        index = modeStore.findExact('id',val); 
        if (index != -1){
            rs = modeStore.getAt(index).data; 
            return rs.name; 
        }
        return val;
    }else{
        return '标准产品';
    }
};

// 状态渲染
var activeRender = function(val){
	if(val == 'true' || val == 1){
		return '<img src="'+homePath+'/public/images/icons/ok.png"></img>';
	}else{
		return '<img src="'+homePath+'/public/images/icons/cross.gif"></img>';
	}
}

//阶段管理窗口
var stageWin = Ext.create('Ext.window.Window', {
 title: '产品阶段管理',
 border: 0,
 height: 300,
 width: 800,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){stageStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'stageGrid',
     columnLines: true,
     store: stageStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加阶段',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
             stageRowEditing.cancelEdit();
             
             var r = Ext.create('Stage', {
                 active: true
             });
             
             stageStore.insert(0, r);
             stageRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除阶段',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('stageGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
             	stageStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = stageStore.getUpdatedRecords();
             var insertRecords = stageStore.getNewRecords();
             var deleteRecords = stageStore.getRemovedRecords();

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
                                 url: homePath+'/public/product/catalog/editstage',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         stageStore.reload();
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
     }],
     plugins: stageRowEditing,
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
         hidden: true
     }, {
         xtype: 'checkcolumn',
         text: '状态',
         dataIndex: 'active',
         stopSelection: false,
         flex: 0.5
     }, {
         text: '名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 1
     }, {
         text: '描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 1.5
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 1.5
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

//产品系列管理窗口
var seriesWin = Ext.create('Ext.window.Window', {
 title: '产品系列管理',
 border: 0,
 height: 300,
 width: 800,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){seriesStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'seriesGrid',
     columnLines: true,
     store: seriesStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
         	seriesRowEditing.cancelEdit();
             
             var r = Ext.create('Series', {
                 active: true
             });
             
             seriesStore.insert(0, r);
             seriesRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('seriesGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
             	seriesStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = seriesStore.getUpdatedRecords();
             var insertRecords = seriesStore.getNewRecords();
             var deleteRecords = seriesStore.getRemovedRecords();

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
                                 url: homePath+'/public/product/catalog/editseries',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         seriesStore.reload();
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
     }],
     plugins: seriesRowEditing,
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
         hidden: true
     }, {
         xtype: 'checkcolumn',
         text: '状态',
         dataIndex: 'active',
         stopSelection: false,
         flex: 0.5
     }, {
         text: '名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 1
     }, {
         text: '代码',
         dataIndex: 'code',
         editor: 'textfield',
         flex: 1
     }, {
         text: '描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 1.5
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 1.5
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

//产品分类管理窗口
var typeWin = Ext.create('Ext.window.Window', {
 title: '产品分类管理',
 border: 0,
 height: 300,
 width: 800,
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

                 // 判断信息是否完整
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
                                 url: homePath+'/public/product/catalog/edittype',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         typeStore.reload();
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
         hidden: true
     }, {
         xtype: 'checkcolumn',
         text: '状态',
         dataIndex: 'active',
         stopSelection: false,
         flex: 0.5
     }, {
         text: '名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 1
     }, {
         text: '描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 1.5
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 1.5
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

// 开发模式管理窗口
var modeWin = Ext.create('Ext.window.Window', {
 title: '开发模式管理',
 border: 0,
 height: 300,
 width: 800,
 modal: true,
 constrain: true,
 closeAction: 'hide',
 layout: 'fit',
 tools: [{
     type: 'refresh',
     tooltip: 'Refresh',
     scope: this,
     handler: function(){modeStore.reload();}
 }],
 items: [{
     xtype: 'gridpanel',
     id: 'modeGrid',
     columnLines: true,
     store: modeStore,
     selType: 'checkboxmodel',
     tbar: [{
         text: '添加',
         iconCls: 'icon-add',
         scope: this,
         handler: function(){
         	modeRowEditing.cancelEdit();
             
             var r = Ext.create('Mode', {
                 active: true
             });
             
             modeStore.insert(0, r);
             modeRowEditing.startEdit(0, 0);
         }
     }, {
         text: '删除',
         iconCls: 'icon-delete',
         scope: this,
         handler: function(){
             var selection = Ext.getCmp('modeGrid').getView().getSelectionModel().getSelection();

             if(selection.length > 0){
             	modeStore.remove(selection);
             }else{
                 Ext.MessageBox.alert('错误', '没有选择删除对象！');
             }
         }
     }, {
         text: '保存修改',
         iconCls: 'icon-save',
         scope: this,
         handler: function(){
             var updateRecords = modeStore.getUpdatedRecords();
             var insertRecords = modeStore.getNewRecords();
             var deleteRecords = modeStore.getRemovedRecords();

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
                                 url: homePath+'/public/product/catalog/editmode',
                                 params: {json: json},
                                 method: 'POST',
                                 success: function(response, options) {
                                     var data = Ext.JSON.decode(response.responseText);

                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         modeStore.reload();
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
     }],
     plugins: modeRowEditing,
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
         hidden: true
     }, {
         xtype: 'checkcolumn',
         text: '状态',
         dataIndex: 'active',
         stopSelection: false,
         flex: 0.5
     }, {
         text: '名称',
         dataIndex: 'name',
         editor: 'textfield',
         flex: 1
     }, {
         text: '描述',
         dataIndex: 'description',
         editor: 'textfield',
         flex: 1.5
     }, {
         text: '备注',
         dataIndex: 'remark',
         editor: 'textfield',
         flex: 1.5
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