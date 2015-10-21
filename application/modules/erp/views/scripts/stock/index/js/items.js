// 请购项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_code"},
             {name: "items_name"},
             {name: "items_description"},
             {name: "items_remark"},
             {name: "items_qty"},
             {name: "items_price"},
             {name: "items_unit"},
             {name: "items_total"},
             {name: "items_warehouse_code"},
             {name: "items_order_id"},
             {name: "items_order_number"},
             {name: "items_order_date"},
             {name: "items_order_currency"},
             {name: "items_order_supplier"},
             {name: "items_order_supplier_name"},
             {name: "items_order_item_id"}]
});

// 数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/stock_index/getreceiveitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		if(Ext.getCmp('receiveSaveBtn').isDisabled()){
    			return false;
    		}
    	}
    }
});

var LODOP;

function print(title, content){
	LODOP = getLodop();  
	LODOP.PRINT_INIT(title);
	LODOP.ADD_PRINT_HTM(10,35,"92%","95%",content);
	
	LODOP.PREVIEW();
}

function printOrder(){
	var number = itemsForm.getForm().findField('number').getValue();
	var id = itemsForm.getForm().findField('id').getValue();
	
	if(id != null){
		Ext.Msg.wait('处理中，请稍后...', '提示');
	    Ext.Ajax.request({
	        url: homePath + '/public/erp/stock_index/getprint/type/in/id/' + id,
	        params: '',
	        method: 'POST',
	        success: function(response, options) {
	        	var data = Ext.JSON.decode(response.responseText);

	            Ext.Msg.hide();
	            
	            print(number, data.info)
	        },
	        failure: function(response){
	            Ext.MessageBox.alert('错误', '打印内容获取失败');
	            Ext.Msg.hide();
	        }
	    });
	}else{
		Ext.MessageBox.alert('错误', '请先保存当前单据！');
	}
}

// 项目列表
var itemsGrid = Ext.create('Ext.grid.Panel', {
	minHeight: 320,
    maxHeight: 320,
    id: 'itemsGrid',
    columnLines: true,
    store: itemsStore,
    tbar: [{
    	text: '选择采购订单项',
    	id: 'itemsSelectBtn',
    	iconCls: 'icon-list',
    	handler: function(){
    		orderWin.show();
    		orderStore.removeAll();
    	}
    }, {
        text: '删除',
        id: 'itemsDeleteBtn',
        iconCls: 'icon-delete',
        handler: function(){
            var selection = itemsGrid.getView().getSelectionModel().getSelection();

            if(selection.length > 0){
                itemsStore.remove(selection);
            }else{
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }
        }
    }],
    plugins: itemsRowEditing,
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        align: 'center',
        hidden: true,
        dataIndex: 'items_id',
        width: 50
    }, {
        text: '物料号',
        dataIndex: 'items_code',
        width: 120
    }, {
        text: '物料名称',
        dataIndex: 'items_name',
        width: 120
    }, {
        text: '采购订单号',
        align: 'center',
        dataIndex: 'items_order_number',
        width: 120
    }, {
        text: '入库数量',
        dataIndex: 'items_qty',
        align: 'center',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '单位',
        align: 'center',
        dataIndex: 'items_unit',
        width: 60
    }, {
        text: '收货仓位',
        renderer: warehouseRender,
        editor: new Ext.form.field.ComboBox({
            editable: false,
            displayField: 'name',
            valueField: 'code',
            triggerAction: 'all',
            lazyRender: true,
            store: warehouseStore,
            queryMode: 'local'
        }),
        dataIndex: 'items_warehouse_code',
        width: 150
    }, {
        text: '物料描述',
        dataIndex: 'items_description',
        width: 200
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 300
    }]
});

var itemsForm = Ext.create('Ext.form.Panel', {
	id: 'itemsForm',
	border: 0,
    url: homePath+'/public/erp/stock_index/editreceive',
    bodyPadding: '2 2 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelWidth: 75
    },
    items: [{
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
        	labelAlign: 'right'
        },
        items: [{
        	xtype: 'hiddenfield',
        	name: 'operate',
        	id: 'operate'
        }, {
        	xtype: 'hiddenfield',
        	name: 'id',
        	id: 'id'
        }, {
        	name: 'number', 
        	id: 'number',
        	xtype:'displayfield',
        	fieldLabel: '单据号',
        	labelWidth: 60,
        	flex: 1
        }, {
        	name: 'date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '收货日期'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
            xtype:'textfield',
            name: 'description', 
            fieldLabel: '描述',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 1
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
            xtype:'textfield',
            name: 'remark', 
            fieldLabel: '备注',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 1
        }]
    }, itemsGrid],
    buttons: [{
    	text: '预览',
    	id: 'print_preview',
    	disabled: true,
    	iconCls: 'icon-preview',
    	handler: function(){
    		printOrder();
    	}
    }, {
        text: '提交',
        id: 'receiveSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            if(form.isValid()){
                // 检查数据有效性
            	if(itemsStore.getCount() > 0){
            		var itemsUpdateRecords = itemsStore.getUpdatedRecords();
                    var itemsInsertRecords = itemsStore.getNewRecords();
                    var itemsDeleteRecords = itemsStore.getRemovedRecords();
                    
                    var valChk = true;
                    var errInfo = '';
                    
                    for(var i = 0; i < itemsUpdateRecords.length; i++){
                    	var rec = itemsUpdateRecords[i];

                    	var code = rec.get('items_code');
                    	var qty = rec.get('items_qty');

                    	if(qty == 0){
                    		valChk = false;
                    		errInfo = code + '数量为0，请填写收货数量!';
                    		
                    		break;
                    	}
                    }
                    
                    if(valChk){
                    	for(var i = 0; i < itemsInsertRecords.length; i++){
                        	var rec = itemsInsertRecords[i];
                        	
                        	var code = rec.get('items_code');
                        	var qty = rec.get('items_qty');

                        	if(qty == 0){
                        		valChk = false;
                        		errInfo = code + '数量为0，请填写收货数量!';
                        		
                        		break;
                        	}
                        }
                    }
                    
                    if(valChk){
                    	var changeRowCnt = itemsUpdateRecords.length + itemsInsertRecords.length + itemsDeleteRecords.length;

                        Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                            if(button == 'yes'){
                                form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    success: function(form, action) {
                                        var data = action.result;

                                        if(data.success){
                                            var errInfo = '';
                                            
                                            if(data.receive_id){
                                                // 当检查到数据有被修改时
                                                //if(changeRowCnt > 0){
                                                    var changeRows = {
                                                		receive_id: data.receive_id,
                                                    	items: {updated: [], inserted: [], deleted: []}
                                                    }

                                                    // 记录更新数据
                                                    for(var i = 0; i < itemsUpdateRecords.length; i++){
                                                        changeRows.items.updated.push(itemsUpdateRecords[i].data)
                                                    }
                                                    // 记录插入数据
                                                    for(var i = 0; i < itemsInsertRecords.length; i++){
                                                        changeRows.items.inserted.push(itemsInsertRecords[i].data)
                                                    }
                                                    // 记录删除数据
                                                    for(var i = 0; i < itemsDeleteRecords.length; i++){
                                                        changeRows.items.deleted.push(itemsDeleteRecords[i].data)
                                                    }

                                                    var json = Ext.JSON.encode(changeRows);

                                                    Ext.Ajax.request({
                                                        url: homePath+'/public/erp/stock_index/edititems',
                                                        params: {json: json},
                                                        method: 'POST',
                                                        success: function(response, options) {
                                                            var data = Ext.JSON.decode(response.responseText);

                                                            // 当保存出错，记录错误信息
                                                            if(!data.success){
                                                                errInfo = data.info;
                                                            }
                                                        },
                                                        failure: function(response){
                                                            errInfo = '收货项目保存提交失败';
                                                        }
                                                    });
                                                //}
                                            }

                                            // 当保存出错，提示错误信息
                                            if(errInfo != ''){
                                                Ext.MessageBox.alert('错误', errInfo);
                                            }else{
                                                Ext.MessageBox.alert('提示', '保存成功');
                                                form.reset();
                                                receiveWin.hide();
                                                receiveStore.loadPage(1);
                                                Ext.getCmp('receiveGrid').getSelectionModel().clearSelections();
                                            }
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
                    	Ext.MessageBox.alert('错误', errInfo);
                    }
            	}else{
            		Ext.MessageBox.alert('错误', '请添加采购项目！');
            	}
            }
        }
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            receiveWin.hide();
        }
    }]
});

// 收货单窗口
var receiveWin = Ext.create('Ext.window.Window', {
	title: '采购收货',
	id: 'receiveWin',
	width: 900,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [itemsForm],
	tools: [{
		type:'help'
	}]
});