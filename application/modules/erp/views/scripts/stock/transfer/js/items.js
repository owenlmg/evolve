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
             {name: "items_warehouse_code_transfer"},
             {name: "items_warehouse_qty"}]
});

// 数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/stock_transfer/gettransferitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		if(Ext.getCmp('transferSaveBtn').isDisabled()){
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
	        url: homePath + '/public/erp/stock_transfer/getprint/id/' + id,
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

function setPrice(code, warehouse_code, rec){
	Ext.Ajax.request({
        url: homePath+'/public/erp/stock_out/getprice',
        params: {code: code, warehouse_code: warehouse_code},
        method: 'POST',
        success: function(response, options) {
        	result = Ext.JSON.decode(response.responseText);
        	
        	// 设置价格
        	rec.set('items_price', result.price);
        },
        failure: function(response){
        	Ext.MessageBox.alert('错误', code + '价格获取提交失败');
        }
    });
}

function setQty(code, warehouse_code, qty, rec){
	// 检查数量
	if(code != '' && warehouse_code != '' && qty > 0){
		var leftQty = 0;
		
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
	        		setPrice(code, warehouse_code, rec);
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

var inputAreaRender = function(val, meta, record){
	if(val == ''){
		meta.style = 'background-color: #FFFFDF';
	}

	return val;
}

var importWin = Ext.create('Ext.window.Window', {
    title: '导入',
    modal: true,
    constrain: true,
    closeAction: 'hide',
    layout: 'fit',
    fieldDefaults: {
        labelAlign: 'left',
        labelWidth: 90,
        anchor: '100%'
    },
    items: [Ext.create('Ext.form.Panel', {
        id: 'importForm',
    	border: 0,
        url: homePath+'/public/erp/stock_in/importitems/type/transfer',
        bodyPadding: '2 2 0',
        fieldDefaults: {
        	msgTarget: 'side',
            labelAlign: 'right',
            labelWidth: 60,
            anchor: '100%'
        },
        items: [{
        	xtype: 'filefield',
            name: 'csv',
            allowBlank: false,
            fieldLabel: 'CSV',
            buttonText: '选择文件'
        }]
    })],
    buttons: [{
    	text: '提交',
        handler: function() {
            var form = Ext.getCmp('importForm').getForm();

            if(form.isValid()){
            	form.submit({
                    waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                        var result = action.result;

                        if(result.success){
                        	var data = result.data;
                        	
                        	form.reset();
                            importWin.hide();
                            
                            itemsRowEditing.cancelEdit();

                        	itemsStore.each(function(rec) {
                        		// 检查导入文件中物料号是否已添加，如已添加则清除
                        		for(var i = 0; i < data.length; i++){
                        			if(data[i].code == rec.get('items_code')){
                        				// 存在
                        				itemsStore.remove(rec);
                        			}
                        		}
                    		});
                        	
                        	for(var i = 0; i < data.length; i++){
                    			var r = Ext.create('Items', {
                        			items_code: data[i].code,
                        			items_name: data[i].name,
                        			items_description: data[i].description,
                        			items_unit: data[i].unit,
                        			items_qty: data[i].qty,
                        			items_warehouse_qty: data[i].warehouse_qty,
                        			items_warehouse_code: data[i].warehouse_code,
                        			items_warehouse_code_transfer: data[i].warehouse_code_transfer,
                                    items_remark: data[i].remark
                                });

                                itemsStore.insert(0, r);
                    		}
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(response, action){
                        var result = action.result;
                        Ext.MessageBox.alert('错误', result.info);
                    }
                });
            }
        }
    }, {
        text: '取消',
        handler: function() {
        	Ext.getCmp('importForm').getForm().reset();
            importWin.hide();
        }
    }]
});

// 项目列表
var itemsGrid = Ext.create('Ext.grid.Panel', {
	minHeight: 320,
    maxHeight: 320,
    id: 'itemsGrid',
    columnLines: true,
    store: itemsStore,
    tbar: [{
    	text: '添加',
    	id: 'itemsAddBtn',
    	iconCls: 'icon-add',
    	handler: function(){
    		itemsRowEditing.cancelEdit();
            
            var r = Ext.create('Items', {
                items_qty: 1,
                items_price: 0,
                items_warehouse_qty: 0
            });

            itemsStore.insert(itemsStore.getCount(), r);
            itemsGrid.getSelectionModel().select(itemsStore.getCount() - 1);
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
    }, {
    	text: '导入',
    	id: 'itemsImportBtn',
    	iconCls: 'icon-csv',
    	tooltip: '根据CSV文件导入物料清单',
    	handler: function(){
    		importWin.show();
    	}
    }],
    plugins: itemsRowEditing,
    features: [{
        ftype: 'summary',
        dock: 'bottom'
    }],
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
        editor: new Ext.form.field.ComboBox({
            displayField: 'text',
            valueField: 'text',
            triggerAction: 'all',
            lazyRender: true,
            typeAhead: true,
            store: codeStore,
            queryMode: 'local',
            listeners: {
            	'beforequery':function(e){
                    var combo = e.combo;
                    if(!e.forceAll){
                        var input = e.query;
                        // 检索的正则
                        var regExp = new RegExp(".*" + input + ".*");
                        // 执行检索
                        combo.store.filterBy(function(record,id){
                            // 得到每个record的项目名称值
                            var text = record.get(combo.displayField)/*.toLowerCase()*/;
                            return regExp.test(text);
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
                		
            			var index = codeStore.findExact('text',newValue);

                		var name = '';
                		var unit = '个';
                		var description = '';
                		
                        if (index != -1){
                        	rs = codeStore.getAt(index).data;

                        	name = rs.name;
                        	unit = rs.unit;
                        	description = rs.description;
                        	
                        	// 检查数量并设置价格
                        	setQty(newValue, rec.get('items_warehouse_code'), rec.get('items_qty'), rec);
                        }

                        // 选择物料号，自动填充名称、描述
                        rec.set('items_name', name);
                        rec.set('items_unit', unit);
                    	rec.set('items_description', description);
            		}
                }
            }
        }),
        renderer: inputAreaRender,
        width: 120
    }, {
        text: '物料名称',
        dataIndex: 'items_name',
        summaryType: 'count',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '项数：' + ((value === 0 || value > 1) ? value + ' 项' : '1 项');
        },
        width: 120
    }, {
        text: '物料描述',
        dataIndex: 'items_description',
        width: 180
    }, {
        text: '出库数量',
        dataIndex: 'items_qty',
        align: 'center',
        editor: new Ext.form.field.Number({
        	listeners: {
            	change: function( sel, newValue, oldValue, eOpts ){
            		var selection = Ext.getCmp('itemsGrid').getView().getSelectionModel().getSelection();
            		
            		if(selection.length > 0){
            			var rec = selection[0];
                		
            			// 检查数量并设置价格
                    	setQty(rec.get('items_code'), rec.get('items_warehouse_code'), newValue, rec);
            		}
                }
            }
        }),
        renderer: inputAreaRender,
        width: 80
    }, {
        text: '单位',
        align: 'center',
        dataIndex: 'items_unit',
        width: 60
    }, /*{
        text: '出库价格',
        align: 'center',
        dataIndex: 'items_price',
        editor: 'numberfield',
        renderer: function(val){
        	return Ext.util.Format.currency(val, '￥', 2);
        },
        width: 80
    }, */{
        text: '出库仓位',
        renderer: warehouseRender,
        editor: new Ext.form.field.ComboBox({
            editable: false,
            displayField: 'name',
            valueField: 'code',
            triggerAction: 'all',
            lazyRender: true,
            store: warehouseStore,
            listeners: {
            	change: function( sel, newValue, oldValue, eOpts ){
            		var selection = Ext.getCmp('itemsGrid').getView().getSelectionModel().getSelection();
            		
            		if(newValue != undefined && selection.length > 0){
            			var rec = selection[0];
                		
            			// 检查数量并设置价格
                    	setQty(rec.get('items_code'), newValue, rec.get('items_qty'), rec);
            		}
                }
            },
            queryMode: 'local'
        }),
        dataIndex: 'items_warehouse_code',
        width: 150
    }, {
        text: '剩余库存',
        dataIndex: 'items_warehouse_qty',
        width: 80
    }, {
        text: '入库仓位',
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
        dataIndex: 'items_warehouse_code_transfer',
        width: 150
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        renderer: inputAreaRender,
        width: 300
    }]
});

var itemsForm = Ext.create('Ext.form.Panel', {
	id: 'itemsForm',
	border: 0,
    url: homePath+'/public/erp/stock_transfer/edittransfer',
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
        	name: 'transaction_type', 
        	id: 'transaction_type',
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'text',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	editable: false,
        	store: transactionListStore,
        	fieldLabel: '类别',
        	allowBlank: false,
        	labelWidth: 60,
        	width: 220,
        	listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
            		if(newValue == '外购入库'){
            			this.up('form').getForm().findField('order_id').show();
            		}else{
            			this.up('form').getForm().findField('order_id').hide();
            		}
            	}
            }
        }, {
        	name: 'order_id',
        	hidden: true,
        	xtype:'combobox',
        	displayField: 'number',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	store: orderListStore,
        	fieldLabel: '采购订单',
        	labelWidth: 70,
        	selectOnFocus: true,
        	autoSelect: false,
        	listeners: {
        	    'beforequery':function(e){
                    var combo = e.combo;
                    if(!e.forceAll){
                        var input = e.query;
                        // 检索的正则
                        var regExp = new RegExp(".*" + input + ".*");
                        // 执行检索
                        combo.store.filterBy(function(record,id){
                            // 得到每个record的项目名称值
                            var text = record.get(combo.displayField);
                            return regExp.test(text);
                        });
                        combo.expand();
                        return false;
                    }
                }
            }
        }, {
        	name: 'date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '发货日期'
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
        id: 'transferSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            if(form.isValid()){
            	if(form.findField('transaction_type').getValue() == '外购入库' 
            		&& (form.findField('order_id').getValue() == '' 
                		|| form.findField('order_id').getValue() == null)){
            		Ext.MessageBox.alert('错误', '请填写采购订单号！');
            	}else{
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
                        	var warehouse_code = rec.get('items_warehouse_code');
                        	var warehouse_code_transfer = rec.get('items_warehouse_code_transfer');
                        	var qty = rec.get('items_qty');

                        	if(code == ''){
                        		valChk = false;
                        		errInfo = '物料号不能为空！';
                        		
                        		break;
                        	}else if(qty == 0){
                        		valChk = false;
                        		errInfo = code + '数量为0，请填写发货数量!';
                        		
                        		break;
                        	}else if(warehouse_code == 0 || warehouse_code_transfer == 0){
                        		valChk = false;
                        		errInfo = code + '仓库信息设置错误!';
                        		
                        		break;
                        	}
                        }
                        
                        if(valChk){
                        	for(var i = 0; i < itemsInsertRecords.length; i++){
                            	var rec = itemsInsertRecords[i];
                            	
                            	var code = rec.get('items_code');
                            	var warehouse_code = rec.get('items_warehouse_code');
                            	var warehouse_code_transfer = rec.get('items_warehouse_code_transfer');
                            	var warehouse_qty = rec.get('items_warehouse_qty');
                            	var qty = rec.get('items_qty');

                            	if(code == ''){
                            		valChk = false;
                            		errInfo = '物料号不能为空！';
                            		
                            		break;
                            	}else if(qty == 0){
                            		valChk = false;
                            		errInfo = code + '数量为0，请填写发货数量!';
                            		
                            		break;
                            	}else if(warehouse_code == 0 || warehouse_code_transfer == 0){
                            		valChk = false;
                            		errInfo = code + '出库信息设置错误!';
                            		
                            		break;
                            	}else if(warehouse_code == warehouse_code_transfer){
                            		valChk = false;
                            		errInfo = code + '出入库仓库设置不能相同!';
                            		
                            		break;
                            	}else if(qty > warehouse_qty){
                            		// 检查库存
                            		valChk = false;
                            		errInfo = code + ' [' + warehouse_code + '] 剩余库存不足!';
                            		
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
                                                
                                                if(data.transfer_id){
                                                    // 当检查到数据有被修改时
                                                    //if(changeRowCnt > 0){
                                                        var changeRows = {
                                                        		transfer_id: data.transfer_id,
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
                                                            url: homePath+'/public/erp/stock_transfer/edititems',
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
                                                                errInfo = '调拨项目保存提交失败';
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
                                                    transferWin.hide();
                                                    transferStore.loadPage(1);
                                                    Ext.getCmp('transferGrid').getSelectionModel().clearSelections();
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
                		Ext.MessageBox.alert('错误', '请添加调拨项目！');
                	}
            	}
            }
        }
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            transferWin.hide();
        }
    }]
});

// 调拨单窗口
var transferWin = Ext.create('Ext.window.Window', {
	title: '库存交易-调拨',
	id: 'transferWin',
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