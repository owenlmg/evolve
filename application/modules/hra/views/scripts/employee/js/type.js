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

var typeStore = Ext.create('Ext.data.Store', {
    model: 'Type',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/hra/employee/gettype/option/data'
    }
});

var typeRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var typeRender = function(val){
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
}

var typeGrid = Ext.create('Ext.grid.Panel', {
    id: 'typeGrid',
    border: 0,
    columnLines: true,
    store: typeStore,
    selType: 'checkboxmodel',
    tbar: [{
        text: '添加用工形式',
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
        text: '删除用工形式',
        iconCls: 'icon-delete',
        scope: this,
        handler: function(){
            var selection = typeGrid.getView().getSelectionModel().getSelection();

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
                                url: homePath+'/public/hra/employee/edittype',
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
                    Ext.MessageBox.alert('错误', '职位信息不完整，请继续填写！');
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
        text: '名称',
        dataIndex: 'name',
        editor: 'textfield',
        flex: 3
    }, {
        text: '描述',
        dataIndex: 'description',
        editor: 'textfield',
        flex: 4
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
});

var typeWin = Ext.create('Ext.window.Window', {
 title: '职位管理',
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
     handler: function(){typeStore.reload();}
 }],
 items: [typeGrid]
});