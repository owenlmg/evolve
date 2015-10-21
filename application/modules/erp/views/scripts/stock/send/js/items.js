// 请购项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_type"},
             {name: "items_code"},
             {name: "items_code_internal"},
             {name: "items_name"},
             {name: "items_description"},
             {name: "items_customer_code"},
             {name: "items_customer_description"},
             {name: "items_remark"},
             {name: "items_qty"},
             {name: "items_price"},
             {name: "items_unit"},
             {name: "items_total"},
             {name: "items_warehouse_code"},
             {name: "items_warehouse_qty"},
             {name: "items_order_id"},
             {name: "items_order_number"},
             {name: "items_order_date"},
             {name: "items_order_currency"},
             {name: "items_order_customer"},
             {name: "items_order_customer_name"},
             {name: "items_order_item_id"}]
});

function setQty(code, warehouse_code, qty, rec){
	// 检查数量
	if(code != '' && warehouse_code != '' && warehouse_code != null && qty > 0){
		var leftQty = 0;
		code = (rec.get('items_code_internal') != '' && rec.get('items_code_internal') != null) ? rec.get('items_code_internal') : code;
		
		Ext.Ajax.request({
	        url: homePath+'/public/erp/stock_index/getqty',
	        params: {code: code, warehouse_code: warehouse_code},
	        method: 'POST',
	        success: function(response, options) {
	        	result = Ext.JSON.decode(response.responseText);
	        	
	        	// 获取数量
	        	leftQty = result.qty;
	        	
	        	// 设置数量
	        	rec.set('items_warehouse_qty', leftQty);
	        	
	        	if(leftQty >= qty){
	    			// 设置价格
	        		//setPrice(code, warehouse_code, rec);
	    		}else{
	    			Ext.MessageBox.alert('错误', code + ' [' + warehouse_code + '] 剩余库存不足！');
	    		}
	        },
	        failure: function(response){
	        	Ext.MessageBox.alert('错误', code + ' [' + warehouse_code + '] 剩余库存获取失败！');
	        }
	    });
	}
}

// 数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/stock_send/getsenditems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		if(Ext.getCmp('sendSaveBtn').isDisabled()){
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
	        url: homePath + '/public/erp/stock_send/getprint/type/send/id/' + id,
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
    	text: '添加销售订单项',
    	id: 'itemsSelectBtn',
    	iconCls: 'icon-list',
    	tooltip: '请选择客户',
    	handler: function(){
    		if(itemsForm.getForm().findField('customer_id').getValue() != null){
    			orderWin.show();
        		orderStore.removeAll();
    		}else{
    			Ext.MessageBox.alert('错误', '请选择客户！');
    		}
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
        text: '产品型号',
        dataIndex: 'items_code',
        width: 120
    }, {
        text: '内部型号',
        dataIndex: 'items_code_internal',
        width: 120
    }, {
        text: '物料名称',
        dataIndex: 'items_name',
        width: 120
    }, {
        text: '销售订单号',
        align: 'center',
        dataIndex: 'items_order_number',
        width: 120
    }, {
        text: '交货数量',
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
        text: '交货仓位',
        renderer: warehouseRender,
        editor: new Ext.form.field.ComboBox({
            displayField: 'name',
            valueField: 'code',
            triggerAction: 'all',
            lazyRender: true,
            store: warehouseStore,
            queryMode: 'local',
            listeners: {
            	'beforequery':function(e){
                    var combo = e.combo;
                    if(!e.forceAll){
                        var input = e.query;
                        var regExp = new RegExp(".*" + input + ".*");
                        combo.store.filterBy(function(record,id){
                            var text = record.get(combo.displayField)/*.toLowerCase()*/;
                            //return regExp.test(text);
                            return (text.toUpperCase().indexOf(input.toUpperCase())!=-1);
                        });
                        combo.expand();
                        return false;
                    }
                },
            	change: function( sel, newValue, oldValue, eOpts ){
            		// 检查物料号是否重复
            		var selection = Ext.getCmp('itemsGrid').getView().getSelectionModel().getSelection();
            		
            		if(newValue != undefined && selection.length > 0){
            			var rec = selection[0];
                		
            			setQty(rec.get('items_code'), newValue, rec.get('items_qty'), rec);
            		}
                }
            }
        }),
        dataIndex: 'items_warehouse_code',
        width: 150
    }, {
        text: '剩余库存',
        dataIndex: 'items_warehouse_qty',
        width: 80
    }, {
        text: '描述',
        dataIndex: 'items_description',
        width: 200
    }, {
        text: '客户产品型号',
        dataIndex: 'items_customer_code',
        width: 200
    }, {
        text: '客户产品描述',
        dataIndex: 'items_customer_description',
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
    url: homePath+'/public/erp/stock_send/editsend',
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
        	name: 'customer_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	store: customerStore,
        	fieldLabel: '客户',
        	labelWidth: 60,
        	selectOnFocus: true,
        	autoSelect: false,
        	listeners: {
        	    'beforequery':function(e){
                    var combo = e.combo;
                    if(!e.forceAll){
                        var input = e.query;
                        var regExp = new RegExp(".*" + input + ".*");
                        combo.store.filterBy(function(record,id){
                            var text = record.get(combo.displayField)/*.toLowerCase()*/;
                            return regExp.test(text);
                        });
                        combo.expand();
                        return false;
                    }
                },
            	change: function( sel, newValue, oldValue, eOpts ){
            		
                }
            },
        	allowBlank: false,
        	flex: 2
        }, {
        	name: 'date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '交货日期'
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
        id: 'sendSaveBtn',
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
                    		errInfo = code + '数量为0，请填写交货数量!';
                    		
                    		break;
                    	}else if(qty > rec.get('items_warehouse_qty')){
                    		valChk = false;
                    		errInfo = code + '数量大于当前仓库剩余库存!';
                    		
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
                        		errInfo = code + '数量为0，请填写交货数量!';
                        		
                        		break;
                        	}else if(qty > rec.get('items_warehouse_qty')){
                        		valChk = false;
                        		errInfo = code + '数量大于当前仓库剩余库存!';
                        		
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
                                            
                                            if(data.send_id){
                                                // 当检查到数据有被修改时
                                                //if(changeRowCnt > 0){
                                                    var changeRows = {
                                                		send_id: data.send_id,
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
                                                        url: homePath+'/public/erp/stock_send/edititems',
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
                                                            errInfo = '交货项目保存提交失败';
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
                                                sendWin.hide();
                                                sendStore.loadPage(1);
                                                Ext.getCmp('sendGrid').getSelectionModel().clearSelections();
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
            		Ext.MessageBox.alert('错误', '请添加销售项目！');
            	}
            }
        }
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            sendWin.hide();
        }
    }]
});

var sendWin = Ext.create('Ext.window.Window', {
	title: '销售交货',
	id: 'sendWin',
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