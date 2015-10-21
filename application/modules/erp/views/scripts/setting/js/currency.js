Ext.define('Currency', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "default"}, 
             {name: "symbol"}, 
             {name: "current_rate"}, 
             {name: "code"}, 
             {name: "name"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

var currencyStore = Ext.create('Ext.data.Store', {
    model: 'Currency',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/setting_currency/getcurrency/option/data'
    },
    autoLoad: true
});

var currencyRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

var currencyGrid = Ext.create('Ext.grid.Panel', {
	title: '货币管理',
	border: 0,
	id: 'currencyGrid',
    columnLines: true,
    store: currencyStore,
    selType: 'checkboxmodel',
    tbar: [{
        text: '添加货币',
        iconCls: 'icon-add',
        scope: this,
        handler: function(){
       	 currencyRowEditing.cancelEdit();
            
            var r = Ext.create('Currency', {
                active: true
            });
            
            currencyStore.insert(0, r);
            currencyRowEditing.startEdit(0, 0);
        }
    }, {
        text: '删除货币',
        iconCls: 'icon-delete',
        scope: this,
        handler: function(){
            var selection = Ext.getCmp('currencyGrid').getView().getSelectionModel().getSelection();

            if(selection.length > 0){
           	 	currencyStore.remove(selection);
            }else{
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }
        }
    }, {
        text: '保存修改',
        iconCls: 'icon-save',
        scope: this,
        handler: function(){
            var updateRecords = currencyStore.getUpdatedRecords();
            var insertRecords = currencyStore.getNewRecords();
            var deleteRecords = currencyStore.getRemovedRecords();

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
                    
                    if(data['name'] == '' || data['code'] == ''){
                        valueCheck = false;
                        break;
                    }
                    
                    changeRows.updated.push(data)
                }
                
                for(var i = 0; i < insertRecords.length; i++){
                    var data = insertRecords[i].data;
                    
                    if(data['name'] == '' || data['code'] == ''){
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
                                url: homePath+'/public/erp/setting_currency/editcurrency',
                                params: {json: json},
                                method: 'POST',
                                success: function(response, options) {
                                    var data = Ext.JSON.decode(response.responseText);

                                    if(data.success){
                                        Ext.MessageBox.alert('提示', data.info);
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
                    Ext.MessageBox.alert('错误', '信息不完整，请继续填写！');
                }
            }else{
                Ext.MessageBox.alert('提示', '没有修改任何数据！');
            }
        }
    }, {
    	text: '汇率管理',
    	id: 'currencyRateBtn',
    	disabled: true,
    	handler: function(){
    		var selection = Ext.getCmp('currencyGrid').getView().getSelectionModel().getSelection();

    		if(selection.length == 1){
    			if(selection[0].get('default')){
    				Ext.MessageBox.alert('错误', '本币不能设置汇率！');
    			}else{
    				currencyRateWin.show();
        			Ext.getCmp('currency_id_to_rate').setValue(selection[0].get('id'));
            		
            		currencyRateStore.load({
                        params: {
                        	currency_id: selection[0].get('id')
                        }
                    });
    			}
    		}else{
    			Ext.MessageBox.alert('错误', '不能一次修改多个货币！');
    		}
    	}
    }, '->', {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
       	 	currencyStore.reload();
        }
    }],
    plugins: currencyRowEditing,
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
        flex: 0.5
    }, {
        xtype: 'checkcolumn',
        text: '启用',
        dataIndex: 'active',
        stopSelection: false,
        flex: 0.5
    }, {
        xtype: 'checkcolumn',
        text: '本币',
        dataIndex: 'default',
        stopSelection: false,
        listeners: {
            checkchange: function (column, recordIndex, checked) {
                if(checked){
                	for(var i = 0, len = currencyStore.data.length; i < len; i++){
                		if(i != recordIndex){
                			var data = currencyStore.getAt(i).set('default', false);
                		}
                	}
                }
            }
        },
        flex: 0.5
    }, {
        text: '代码',
        align: 'center',
        dataIndex: 'code',
        editor: 'textfield',
        flex: 1
    }, {
        text: '货币符号',
        align: 'center',
        dataIndex: 'symbol',
        editor: 'textfield',
        flex: 1
    }, {
        text: '当前汇率',
        align: 'center',
        dataIndex: 'current_rate',
        flex: 1
    }, {
        text: '名称',
        dataIndex: 'name',
        editor: 'textfield',
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
    }],
    listeners: {
    	select: function( sel, record, selected, eOpts ){
    		Ext.getCmp('currencyRateBtn').enable();
        }
    }
});