// 组数据模型
Ext.define('Group', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "type"}, 
             {name: "active"}, 
             {name: "name"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 组数据源
var groupStore = Ext.create('Ext.data.Store', {
    model: 'Group',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/bpartner/getgroup/option/data'
    }
});

// 组列表数据源
var groupListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/bpartner/getgroup/option/list'
    }
});

// 组编辑插件
var groupRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 组管理窗口
var groupWin = Ext.create('Ext.window.Window', {
    title: '业务伙伴组管理',
    border: 0,
    height: 400,
    width: 700,
    modal: true,
    constrain: true,
    layout: 'fit',
    closeAction: 'hide',
    maximizable: true,
    tools: [{
        type: 'refresh',
        tooltip: 'Refresh',
        scope: this,
        handler: function(){groupStore.reload();}
    }],
    items: [{
        xtype: 'gridpanel',
        border: 0,
        id: 'groupGrid',
        columnLines: true,
        store: groupStore,
        selType: 'checkboxmodel',
        tbar: [{
            xtype: 'combobox',
            id: 'search_group_type',
            displayField: 'name',
            valueField: 'id',
            width: 100,
            editable: false,
            store: partnerTypeStore,
            listeners: {
                change: function(combo, newValue, oldValue, eOpts){
                    if(newValue != oldValue){
                    	groupStore.load({
                            params: {
                            	type: newValue
                            }
                        });
                    }
                }
            }
        }, {
            text: '添加',
            disabled: bpartnerAdminDisabled,
            iconCls: 'icon-add',
            scope: this,
            handler: function(){
            	groupRowEditing.cancelEdit();
                
                var r = Ext.create('Group', {
                    active: true
                });
                
                groupStore.insert(0, r);
                groupRowEditing.startEdit(0, 0);
            }
        }, {
            text: '删除',
            disabled: bpartnerAdminDisabled,
            iconCls: 'icon-delete',
            disable: true,
            scope: this,
            handler: function(){
                var selection = Ext.getCmp('groupGrid').getView().getSelectionModel().getSelection();

                if(selection.length > 0){
                	groupStore.remove(selection);
                }else{
                    Ext.MessageBox.alert('错误', '没有选择删除对象！');
                }
            }
        }, {
            text: '保存修改',
            disabled: bpartnerAdminDisabled,
            iconCls: 'icon-save',
            scope: this,
            handler: function(){
                var updateRecords = groupStore.getUpdatedRecords();
                var insertRecords = groupStore.getNewRecords();
                var deleteRecords = groupStore.getRemovedRecords();

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

                                Ext.Msg.wait('保存中，请稍后...', '提示');
                                Ext.Ajax.request({
                                    url: homePath+'/public/erp/bpartner/editgroup',
                                    params: {type: Ext.getCmp('search_group_type').getValue(), json: json},
                                    method: 'POST',
                                    success: function(response, options) {
                                        var data = Ext.JSON.decode(response.responseText);

                                        if(data.success){
                                            Ext.MessageBox.alert('提示', data.info);
                                            groupStore.reload();
                                            groupListStore.reload();
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
        plugins: groupRowEditing,
        viewConfig: {
            stripeRows: false,// 取消偶数行背景色
            getRowClass: function(record) {
                if(record.get('active') == 0){
                    return 'gray-row';
                }
            }
        },
        columns: [{
            xtype: 'rownumberer'
        }, {
            xtype: 'checkcolumn',
            text: '启用',
            dataIndex: 'active',
            stopSelection: false,
            flex: 0.5
        }, {
            text: '名称',
            dataIndex: 'name',
            editor: 'textfield',
            flex: 1
        }, {
            text: 'ID',
            dataIndex: 'id',
            hidden: true,
            flex: 1
        }, {
            text: '描述',
            dataIndex: 'description',
            editor: 'textfield',
            flex: 2
        }, {
            text: '备注',
            dataIndex: 'remark',
            editor: 'textfield',
            flex: 1
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