Ext.define('Room', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
             {name: "active"},
             {name: "name"},
             {name: "projector"},
             {name: "tel"},
             {name: "qty"},
             {name: "remark"},
             {name: "create_time"},
             {name: "update_time"},
             {name: "creater"},
             {name: "updater"}]
});

var roomStore = Ext.create('Ext.data.Store', {
    model: 'Room',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/res/meeting/getroom/option/data'
    },
    autoLoad: true
});

/*var roomListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/res/meeting/getroom/option/list'
    },
    autoLoad: true
});*/

var roomRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var roomGrid = Ext.create('Ext.grid.Panel', {
    id: 'roomGrid',
    border: 0,
    columnLines: true,
    store: roomStore,
    selType: 'checkboxmodel',
    tbar: [{
        text: '添加会议室',
        iconCls: 'icon-add',
        scope: this,
        handler: function(){
            roomRowEditing.cancelEdit();
            
            var r = Ext.create('Room', {
                active: true,
                qty: 1
            });
            
            roomStore.insert(0, r);
            roomRowEditing.startEdit(0, 0);
        }
    }, {
        text: '删除会议室',
        iconCls: 'icon-delete',
        scope: this,
        handler: function(){
            roomStore.remove(roomGrid.getView().getSelectionModel().getSelection());
        }
    }, {
        text: '保存修改',
        iconCls: 'icon-save',
        scope: this,
        handler: function(){
            var updateRecords = roomStore.getUpdatedRecords();
            var insertRecords = roomStore.getNewRecords();
            var deleteRecords = roomStore.getRemovedRecords();

            // 判断是否有修改数据
            if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                var changeRows = {
                        updated:    [],
                        inserted:   [],
                        deleted:    []
                }

                // 判断职位信息是否完整
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
                                url: homePath+'/public/res/meeting/editroom',
                                params: {json: json},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);

                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
                                        roomStore.reload();
                                        //roomListStore.reload();
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
            roomStore.reload();
        }
    }],
    plugins: roomRowEditing,
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
        width: 50
    }, {
        text: '名称',
        dataIndex: 'name',
        editor: 'textfield',
        flex: 2
    }, {
        xtype: 'checkcolumn',
        text: '投影仪',
        dataIndex: 'projector',
        stopSelection: false,
        width: 70
    }, {
        text: '电话',
        dataIndex: 'tel',
        editor: 'textfield',
        align: 'center',
        width: 120
    }, {
        text: '容纳人数',
        dataIndex: 'qty',
        editor: 'numberfield',
        align: 'center',
        width: 80
    }, {
        text: '备注',
        dataIndex: 'remark',
        editor: 'textfield',
        flex: 4
    }, {
        text: '创建人',
        hidden: true,
        dataIndex: 'creater',
        width: 100
    }, {
        text: '创建时间',
        hidden: true,
        dataIndex: 'create_time',
        width: 140
    }, {
        text: '更新人',
        hidden: true,
        dataIndex: 'updater',
        width: 100
    }, {
        text: '更新时间',
        hidden: true,
        dataIndex: 'update_time',
        width: 140
    }]
});

var roomWin = Ext.create('Ext.window.Window', {
    title: '会议室管理',
    height: 300,
    width: 800,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    layout: 'fit',
    items: [roomGrid]
});