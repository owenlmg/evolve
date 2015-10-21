// 请购项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_invoice_id"},
             {name: "items_order_id"},
             {name: "items_order_date"},
             {name: "items_order_number"},
             {name: "items_order_item_id"},
             {name: "items_order_currency"},
             {name: "items_order_currency_rate"},
             {name: "items_order_tax_id"},
             {name: "items_order_tax_name"},
             {name: "items_order_tax_rate"},
             {name: "items_code"},
             {name: "items_name"},
             {name: "items_description"},
             {name: "items_remark"},
             {name: "items_price"},
             {name: "items_price_tax"},
             {name: "items_qty"},
             {name: "items_unit"},
             {name: "items_total"},
             {name: "items_total_tax"},
             {name: "items_total_no_tax"},
             {name: "items_forein_total"},
             {name: "items_forein_total_tax"},
             {name: "items_forein_total_no_tax"},
             {name: "create_time",type:'date',dateFormat: 'timestamp'},
             {name: "update_time",type:'date',dateFormat: 'timestamp'},
             {name: "create_user"},
             {name: "update_user"},
             {name: "creater"},
             {name: "updater"}]
});

var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_invoice_index/getinvoiceitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

function getCurrentCurrency(){
	return Ext.getCmp('invoice_form').getForm().findField("currency").getValue();
}

var LODOP;

function print(title, content){
	LODOP = getLodop();  
	LODOP.PRINT_INIT(title);
	LODOP.ADD_PRINT_HTM(10,35,"92%","95%",content);
	
	LODOP.PREVIEW();
}

function printInvoice(){
	var number = invoiceForm.getForm().findField('number').getValue();
	var id = invoiceForm.getForm().findField('id').getValue();
	
	if(id != null){
		Ext.Msg.wait('处理中，请稍后...', '提示');
	    Ext.Ajax.request({
	        url: homePath + '/public/erp/purchse_invoice_index/getprint',
	        params: {id: id},
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
    selModel: {
        mode: 'MULTI'
    },
    store: itemsStore,
    features: [{
        ftype: 'summary',
        dock: 'bottom'
    }],
    viewConfig: {
        stripeRows: false
    },
    tbar: [{
    	text: '选择采购订单',
    	id: 'itemsSelectBtn',
    	iconCls: 'icon-list',
    	handler: function(){
    		orderWin.show();
    		orderStore.removeAll();
    		Ext.getCmp('search_order_supplier_id').setValue(invoiceForm.getForm().findField('supplier_id').getValue());
    		Ext.getCmp('search_order_currency').setValue(invoiceForm.getForm().findField('currency').getValue());
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
    }, '->', {
    	text: '审核日志',
    	handler: function(){
    		viewReviewInfo(invoiceForm.getForm().findField('review_info').getValue());
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
    	text: '订单号',
    	align: 'center',
    	dataIndex: 'items_order_number',
    	width: 100
    }, {
        text: '物料号',
        dataIndex: 'items_code',
        width: 120
    }, {
        text: '名称',
        dataIndex: 'items_name',
        summaryType: 'count',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '项数：' + ((value === 0 || value > 1) ? value + ' 项' : '1 项');
        },
        width: 120
    }, {
        text: '描述',
        dataIndex: 'items_description',
        width: 180
    }, {
        text: '数量',
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
        text: '单价',
        dataIndex: 'items_price',
        renderer: function(value, meta, rec){
        	return setMoney(value, getCurrentCurrency());
        },
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '合计：';
        },
        width: 100
    }, {
        text: '金额',
        dataIndex: 'items_total',
        renderer: function(value, metaData, record, rowIdx, colIdx, store, view) {
        	var total = record.get('items_qty') * record.get('items_price');
        	
        	return setMoney(total, getCurrentCurrency());
        },
        summaryType: function(records){
            var i = 0,
                length = records.length,
                total = 0,
                record;

            for (; i < length; ++i) {
                record = records[i];
                total += record.get('items_qty') * record.get('items_price');
            }
            return total;
        },
        summaryRenderer: function(val){
        	return setTotalMoney(val, getCurrentCurrency());
        },
        width: 120
    }, {
    	text: '价格含税',
    	align: 'center',
    	dataIndex: 'items_price_tax',
    	renderer: function(val, cellmeta, record, rowIndex){
    		if(val == 1){
    			return '<img src="'+homePath+'/public/images/icons/ok.png"></img>';
    		}else{
    			return '<img src="'+homePath+'/public/images/icons/cross.gif"></img>';
    		}
    	},
    	width: 70
    }, {
    	text: '币种',
    	hidden: true,
    	dataIndex: 'items_order_currency',
    	width: 70
    }, {
    	text: '汇率',
    	hidden: true,
    	dataIndex: 'items_order_currency_rate',
    	width: 70
    }, {
    	text: '税种',
    	dataIndex: 'items_order_tax_name',
    	width: 70
    }, {
    	text: '税率',
    	dataIndex: 'items_order_tax_rate',
    	width: 70
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 200
    }]
});

var invoiceForm = Ext.create('Ext.form.Panel', {
	id: 'invoice_form',
	border: 0,
    url: homePath+'/public/erp/purchse_invoice_index/editinvoice',
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
        	name: 'state',
        	id: 'invoice_state'
        }, {
        	xtype: 'hiddenfield',
        	name: 'review_info',
        	id: 'review_info'
        }, {
        	xtype: 'hiddenfield',
        	name: 'operate',
        	id: 'operate'
        }, {
        	xtype: 'hiddenfield',
        	name: 'id',
        	id: 'id'
        }, {
        	xtype: 'hiddenfield',
        	name: 'current_step',
        	id: 'current_step'
        }, {
        	xtype: 'hiddenfield',
        	name: 'last_step',
        	id: 'last_step'
        }, {
        	xtype: 'hiddenfield',
        	name: 'to_finish',
        	id: 'to_finish'
        }, {
        	xtype: 'hiddenfield',
        	name: 'next_step',
        	id: 'next_step'
        }, {
        	name: 'number', 
        	id: 'number',
        	xtype:'displayfield',
        	fieldLabel: '单据号',
        	labelWidth: 60,
        	flex: 1
        }, {
        	name: 'supplier_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	store: supplierStore,
        	fieldLabel: '供应商',
        	labelWidth: 60,
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
                },
            	change: function( sel, newValue, oldValue, eOpts ){
            		var index = supplierStore.findExact('id', newValue);
                    
                    if(index != -1){
                        var currency = supplierStore.getAt(index).data.currency;
                        
                        Ext.getCmp('invoice_form').getForm().findField("currency").setValue(currency);
                    }
                    
                    Ext.getCmp('itemsSelectBtn').enable();
                    
                    itemsStore.removeAll();
                }
            },
        	allowBlank: false,
        	flex: 2
        }, {
        	name: 'currency', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'name',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	editable: false,
        	allowBlank: false,
        	store: currencyStore,
        	fieldLabel: '币种',
        	labelWidth: 50,
        	listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
                    itemsStore.removeAll();
                }
        	},
        	width: 120
        }, {
        	name: 'invoice_date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '发票日期'
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
    		printInvoice();
    	}
    }, {
    	text: '审核',
    	id: 'invoiceReviewBtn',
    	handler: function(){
    		reviewInvoice();
    	}
    }, {
        text: '提交',
        id: 'invoiceSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();

            if(form.isValid()){
                // 检查数据有效性
            	if(itemsStore.getCount() > 0){
            		var itemsUpdateRecords = itemsStore.getUpdatedRecords();
                    var itemsInsertRecords = itemsStore.getNewRecords();
                    var itemsDeleteRecords = itemsStore.getRemovedRecords();
                    
                	var changeRowCnt = itemsUpdateRecords.length + itemsInsertRecords.length + itemsDeleteRecords.length;

                    Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                        if(button == 'yes'){
                            form.submit({
                                waitMsg: '提交中，请稍后...',
                                success: function(form, action) {
                                    var data = action.result;

                                    if(data.success){
                                        var errInfo = '';
                                        
                                        if(data.invoice_id){
                                            var changeRows = {
                                        		invoice_id: data.invoice_id,
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
                                            var operate = Ext.getCmp('operate').getValue();

                                            Ext.Ajax.request({
                                                url: homePath+'/public/erp/purchse_invoice_index/edititems',
                                                params: {json: json, operate: operate},
                                                method: 'POST',
                                                success: function(response, options) {
                                                    var data = Ext.JSON.decode(response.responseText);

                                                    if(!data.success){
                                                        errInfo = data.info;
                                                    }
                                                },
                                                failure: function(response){
                                                    errInfo = '发票项目保存提交失败';
                                                }
                                            });
                                        }

                                        // 当保存发票项目出错，提示错误信息
                                        if(errInfo != ''){
                                            Ext.MessageBox.alert('错误', errInfo);
                                        }else{
                                            Ext.MessageBox.alert('提示', '保存成功');
                                            form.reset();
                                            invoiceWin.hide();
                                            invoiceStore.loadPage(1);
                                            Ext.getCmp('invoiceGrid').getSelectionModel().clearSelections();
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
            		Ext.MessageBox.alert('错误', '请添加发票项目！');
            	}
            }
        }
    }, {
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            invoiceWin.hide();
        }
    }]
});

// 发票窗口
var invoiceWin = Ext.create('Ext.window.Window', {
	title: '采购发票',
	id: 'invoiceWin',
	width: 900,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [invoiceForm]
});