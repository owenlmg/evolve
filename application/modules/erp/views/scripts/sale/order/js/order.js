Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_transfer_type"},
             {name: "items_active"},
             {name: "items_order_id"},
             {name: "state",type: "int"},
             {name: "items_type"},
             {name: "items_code"},
             {name: "items_customer_code"},
             {name: "items_name"},
             {name: "items_product_type"},
             {name: "items_product_series"},
             {name: "items_description"},
             {name: "items_customer_description"},
             {name: "items_remark"},
             {name: "items_warehouse_code"},
             {name: "items_price", type: "float"},
             {name: "items_price_get", type: "float"},
             {name: "items_price_tax"},
             {name: "items_qty", type: "float"},
             {name: "items_qty_send", type: "float"},// 交货数量
             {name: "items_unit"},
             {name: "items_total", type: "float"},
             {name: "items_request_date",type: 'date',dateFormat: 'Y-m-d'},
             {name: "create_time",type:'date',dateFormat: 'timestamp'},
             {name: "update_time",type:'date',dateFormat: 'timestamp'},
             {name: "create_user"},
             {name: "update_user"},
             {name: "creater"},
             {name: "updater"}]
});

//角色设置数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_order/getorderitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		if(Ext.getCmp('orderSaveBtn').isDisabled() 
    				|| (!Ext.getCmp('transfer').isHidden() && Ext.getCmp('transfer_type').getValue() == '取消')){
    			return false;
    		}else{
    			var record = e.record;

    			if(record.get('items_code') != '' && (e.field == 'items_name' || e.field == 'items_description')){
        			e.cancel = true;
        		}
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
	var number = orderForm.getForm().findField('number').getValue();
	var tpl_id = orderForm.getForm().findField('tpl_id').getValue();
	var id = orderForm.getForm().findField('id').getValue();
	
	if(id != null){
		Ext.Msg.wait('处理中，请稍后...', '提示');
	    Ext.Ajax.request({
	        url: homePath + '/public/erp/sale_order/getprint/id/' + id + '/tpl_id/' +　tpl_id,
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

function getCurrentCurrency(){
	return Ext.getCmp('order_form').getForm().findField("currency").getValue();
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
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(!record.get('items_active')){
                return 'gray-row';
            }
        }
    },
    tbar: [{
        text: '添加',
        id: 'itemsAddBtn',
        iconCls: 'icon-add',
        handler: function(){
            itemsRowEditing.cancelEdit();
            
            var r = Ext.create('Items', {
                items_active: true,
                items_price_tax: false,
                items_qty: 0,
                items_unit: '个',
                items_price: 0,
                items_line_total: 0,
                items_request_date: Ext.Date.clearTime(new Date())
            });

            itemsStore.insert(itemsStore.getCount(), r);
            itemsGrid.getSelectionModel().select(itemsStore.getCount() - 1);
            //itemsRowEditing.startEdit(0, 0);
        }
    }, {
        text: '删除',
        id: 'itemsDeleteBtn',
        iconCls: 'icon-delete',
        handler: function(){
            var selection = itemsGrid.getView().getSelectionModel().getSelection();

            if(selection.length > 0){
            	if(Ext.getCmp('operate').getValue() == 'transfer' 
            		&& selection[0].get('items_qty_send') > 0){
            		Ext.MessageBox.alert('错误', '当前记录已有交货，不能删除！');
            	}else{
            		itemsStore.remove(selection);
            	}
            }else{
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }
        }
    }, '->', {
    	text: '变更内容',
    	handler: function(){
    		var id = orderForm.getForm().findField('transfer_id').getValue();
    		var type = orderForm.getForm().findField('transfer_type').getValue();
    		var description = orderForm.getForm().findField('transfer_description').getValue();
    		
    		if(id != ''){
    			viewTransferContent(id, type, description);
    		}
    	}
    }, {
    	text: '变更日志',
    	handler: function(){
    		viewTransferInfo(orderForm.getForm().findField('id').getValue());
    	}
    }, {
    	text: '审核日志',
    	handler: function(){
    		viewReviewInfo(orderForm.getForm().findField('review_info').getValue());
    	}
    }],
    plugins: itemsRowEditing,
    columns: [{
        xtype: 'rownumberer'
    }, {
        xtype: 'checkcolumn',
        text: '启用',
        dataIndex: 'items_active',
        stopSelection: false,
        listeners: {
        	checkchange: function(chk, rowIndex, checked, eOpts){
        		var rec = itemsStore.getAt(rowIndex);
        		
        		if(!checked && rec.get('items_qty_send') > 0){
        			Ext.MessageBox.show({
                        title: '错误',
                        msg: '当前记录已有交货，不能取消！',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.WARNING
                    });
        			
        			var rec = itemsStore.getAt(rowIndex);
        			rec.set('items_active', true);
        		}
        	}
        },
        width: 50
    }, {
        text: 'ID',
        align: 'center',
        hidden: true,
        dataIndex: 'items_id',
        width: 50
    }, {
    	text: '类别',
    	align: 'center',
    	dataIndex: 'items_type',
    	renderer: function(val, cellmeta, record, rowIndex){
    		if(val == 'catalog'){
    			return '产品';
    		}else{
    			return '物料';
    		}
    	},
    	width: 50
    }, {
        text: '产品型号',// 内部型号 / 物料号
        dataIndex: 'items_code',
        editor: new Ext.form.field.ComboBox({
            displayField: 'code',
            valueField: 'code',
            triggerAction: 'all',
            lazyRender: true,
            store: codeStore,
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
            		
            		if(selection.length > 0){
            			var rec = selection[0];
                		
                		index = itemsStore.findExact('items_code', newValue);
                        if (index != -1){
                        	Ext.MessageBox.alert('错误', '订单项重复！');
                        	sel.clearValue();
                        	itemsRowEditing.cancelEdit();
                        }else{
                    		var index = codeStore.findExact('code',newValue);

                    		var type = '';
                    		var name = '';
                    		var description = '';
                    		var customer_code = '';
                    		var customer_description = '';
                    		var product_type = '';
                    		var product_series = '';
                    		
                            if (index != -1){
                            	rs = codeStore.getAt(index).data;

                            	type = rs.type;
                            	name = rs.name;
                            	description = rs.description;
                            	customer_code = rs.customer_code;
                            	customer_description = rs.customer_description;
                            	product_type = rs.product_type;
                            	product_series = rs.product_series;
                            }

                            // 选择物料号，自动填充名称、描述
                            rec.set('items_type', type);
                            rec.set('items_name', name);
                        	rec.set('items_description', description);
                            rec.set('items_customer_code', customer_code);
                        	rec.set('items_customer_description', customer_description);
                            rec.set('items_product_type', product_type);
                        	rec.set('items_product_series', product_series);
                        }
            		}
                }
            }
        }),
        width: 120
    }, {
        text: '数量',
        dataIndex: 'items_qty',
        align: 'center',
        editor: new Ext.form.field.Number({
        	listeners: {
        		change: function( num, newValue, oldValue, eOpts ){
        			//if(Ext.getCmp('transfer').isHidden()){
        				Ext.Msg.wait('处理中，请稍后...', '提示');
            			
            			var selection = itemsGrid.getView().getSelectionModel().getSelection();
            			
                    	if(selection.length > 0){
                    		var rec = selection[0];
                        	
                        	if(rec.get('items_code') != ''){
                        		var form = Ext.getCmp('order_form').getForm();
                        		var customer_id = form.findField("customer_id").getValue();
                        		var currency = form.findField("currency").getValue();
                        		var price_tax = form.findField("price_tax").getValue() ? 1 : 0;
                            	var date = Ext.util.Format.date(form.findField("order_date").getValue(), 'Y-m-d');
                            	
                            	Ext.Ajax.request({
                                    url: homePath+'/public/erp/sale_price/getprice',
                                    params: {
                                    	code: rec.get('items_code'), 
                                    	customer_id: customer_id, 
                                    	currency: currency, 
                                    	fix: 0, 
                                    	date: date, 
                                    	qty: newValue
                                	},
                                    method: 'POST',
                                    success: function(response, options) {
                                    	result = Ext.JSON.decode(response.responseText);
                                    	
                                    	// 获取价格
                                    	var price = result.price['price'];
                                    	var price_tax = result.price['price_tax'] == 1 ? true : false;

                                    	rec.set('items_price_get', price);
                                    	rec.set('items_price', price);
                                    	rec.set('items_price_tax', price_tax);
                                    	
                                    	Ext.Msg.hide();
                                    },
                                    failure: function(response){
                                    	Ext.MessageBox.alert('错误', code + '价格获取提交失败');
                                    }
                                });
                        	}
                    	}
        			//}
            	}
        	}
        }),
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
    	text: '已交货',
    	align: 'center',
    	dataIndex: 'items_qty_send',
    	renderer: function(val, meta, record){
        	meta.style = 'background-color: #FFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '单位',
        align: 'center',
        editor: 'textfield',
        dataIndex: 'items_unit',
        width: 60
    }, {
        xtype: 'checkcolumn',
        text: '含税',
        dataIndex: 'items_price_tax',
        stopSelection: false,
        listeners: {
        	checkchange: function(chk, rowIndex, checked, eOpts){
        		var rec = itemsStore.getAt(rowIndex);
        		var price = 0;
        		var form = Ext.getCmp('order_form').getForm();
        		var rate = parseFloat(form.findField('tax_rate').getValue());
        		
        		if(checked){
        			price = rec.get('items_price') * (rate + 1);
        		}else{
        			price = rec.get('items_price') / (rate + 1);
        		}
        		
        		rec.set('items_price', price);
        	}
        },
        width: 50
    }, {
        text: '单价',
        /*editor: new Ext.form.NumberField({  
            decimalPrecision: 8,
            minValue: 0
        }),*/
        renderer: function(value, meta, rec){
        	return setMoney(value, getCurrentCurrency());
        },
        dataIndex: 'items_price',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '合计：';
        },
        width: 80
    }, {
        text: '金额',
        dataIndex: 'items_total',
        renderer: function(value, metaData, record, rowIdx, colIdx, store, view) {
        	var total = record.get('items_qty') * record.get('items_price');
        	
        	return setTotalMoney(total, getCurrentCurrency());
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
        text: '需求日期',
        align: 'center',
        dataIndex: 'items_request_date',
        renderer: Ext.util.Format.dateRenderer('Y-m-d'),
        editor: {
            xtype: 'datefield',
            editable: false,
            format: 'Y-m-d'
        },
        width: 110
    }, {
    	text: '产品类别',
    	dataIndex: 'items_product_type',
    	width: 100
    }, {
    	text: '产品系列',
    	dataIndex: 'items_product_series',
    	width: 100
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
        text: '客户产品型号',
        dataIndex: 'items_customer_code',
        width: 120
    }, {
        text: '客户产品描述',
        dataIndex: 'items_customer_description',
        width: 180
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 200
    }]
});

// 设置价格
function setItemPrice(index, code, qty, customer_id, date, currency){
	Ext.Ajax.request({
        url: homePath+'/public/erp/sale_price/getprice',
        params: {code: code, customer_id: customer_id, fix: 0, date: date, qty: qty, currency: currency},
        method: 'POST',
        success: function(response, options) {
        	result = Ext.JSON.decode(response.responseText);
        	
        	// 获取申请ID及下单数量
        	var price = result.price['price'];

            itemsStore.getAt(index).set('items_price', price);
        },
        failure: function(response){
        	Ext.MessageBox.alert('错误', code + '价格获取提交失败');
        }
    });
}

// 刷新价格
function refreshPrice(){
	Ext.Msg.wait('处理中，请稍后...', '提示');
	
	var customer_id = Ext.getCmp('order_form').getForm().findField("customer_id").getValue();
	var currency = Ext.getCmp('order_form').getForm().findField("currency").getValue();
	var date = Ext.util.Format.date(Ext.getCmp('order_form').getForm().findField("order_date").getValue(), 'Y-m-d');
	
	var items = itemsStore.data.items;
	var items_insert = [];
	
	for(var i = 0; i < items.length; i++){
		if(items[i].get('items_code')){
			var code = items[i].get('items_code');
			var qty = items[i].get('items_qty');
			
			setItemPrice(i, code, qty, customer_id, date, currency);
		}
	}
	
	Ext.Msg.hide();
}

var orderForm = Ext.create('Ext.form.Panel', {
	id: 'order_form',
	border: 0,
    url: homePath+'/public/erp/sale_order/editorder',
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
            name: 'transfer_id',
            id: 'order_transfer_id'
        }, {
        	xtype: 'hiddenfield',
        	name: 'state',
        	id: 'order_state'
        }, {
        	xtype: 'hiddenfield',
        	name: 'review_info',
        	id: 'review_info'
        }, {
        	xtype: 'hiddenfield',
        	name: 'transfer_info',
        	id: 'transfer_info'
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
            name: 'hand_number',
            id: 'hand_number',
            hidden: true,
            xtype: 'textfield',
            fieldLabel: '单据号',
            labelWidth: 60,
            flex: 1
        }, {
            name: 'hand',
            id: 'hand',
            xtype: 'checkboxfield',
            fieldLabel: '补单',
            labelAlign: 'right',
            checked: false,
            labelWidth: 45,
            listeners: {
                change:  function (chk, newValue, oldValue, eOpts){
                    if(newValue){
                        Ext.getCmp('hand_number').show();
                        Ext.getCmp('number').hide();
                    }else{
                        Ext.getCmp('hand_number').hide();
                        Ext.getCmp('number').show();
                    }
                }
            },
            width: 60
        }, {
        	name: 'type_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
           	editable: false,
           	store: typeListStore,
           	fieldLabel: '类别',
           	labelWidth: 50,
        	allowBlank: false,
           	width: 160
        }, {
        	name: 'sales_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	editable: false,
        	allowBlank: false,
        	store: salesListStore,
        	fieldLabel: '销售员',
        	labelWidth: 60,
        	width: 160
        }, {
        	name: 'create_user_name', 
        	xtype:'displayfield',
        	fieldLabel: '制单',
        	labelWidth: 60,
        	allowBlank: false,
        	width: 170
        }, {
        	name: 'order_date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '订单日期'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
        	labelAlign: 'right'
        },
        items: [{
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
            		var form = this.up('form').getForm();
            		
            		if(form.findField('operate').getValue() != ''){
            			customerAddressCodeListStore.load({
                            params: {
                                partner_id: newValue
                            },
                            callback: function(records, operation, success) {
                                var index = customerStore.findExact('id', newValue);
                                
                                if(index != -1){
                                	var customer = customerStore.getAt(index).data;
                                    
                                    form.findField("currency").setValue(customer.currency);
                                    form.findField("tax_id").setValue(customer.tax_id);
                                    form.findField("tax_name").setValue(customer.tax_name);
                                    form.findField("tax_rate").setValue(customer.tax_rate);
                                    form.findField("customer_address_code").setValue(records[0].get('code'));

                                    if(Ext.getCmp('operate').getValue() != ''){
                                        refreshPrice();
                                    }
                                }
                            }
                        });
            		}else{
            			customerAddressCodeListStore.load({
                            params: {
                                partner_id: newValue
                            }
                        });
            		}
        			
        			codeStore.load({
                        params: {
                            customer_id: newValue
                        }
                    });
                }
            },
        	allowBlank: false,
        	flex: 1
        }, {
        	name: 'customer_address_code',
        	id: 'form_customer_address_code',
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'code',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	store: customerAddressCodeListStore,
        	fieldLabel: '客户地址简码',
        	labelWidth: 90,
        	listeners: {
        		'beforequery':function(e){
                    var combo = e.combo;
                    if(!e.forceAll){
                        var input = e.query;
                        var regExp = new RegExp(".*" + input + ".*");
                        combo.store.filterBy(function(record,id){
                            var text = record.get(combo.displayField);  
                            return regExp.test(text);
                        });
                        combo.expand();
                        return false;
                    }
                }
            },
        	width: 270
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
        	width: 110
        }, {
        	name: 'tax_id',
        	xtype: 'hiddenfield'
        }, {
        	name: 'tax_name',
        	xtype: 'displayfield',
        	fieldLabel: '税种',
        	labelWidth: 40,
        	width: 170
        }, {
        	name: 'tax_rate',
        	xtype: 'displayfield',
        	fieldLabel: '税率',
        	labelWidth: 40,
        	width: 80
        }, {
        	name: 'price_tax',
        	xtype: 'checkboxfield',
        	fieldLabel: '价格含税',
        	checked: false,
            hidden: true,
        	labelWidth: 70,
        	width: 280
        }, {
        	name: 'request_date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
            hidden: true,
        	fieldLabel: '需求日期'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
            xtype:'textfield',
            name: 'settle_way', 
            fieldLabel: '结算方式',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 1
        }, {
            xtype:'textfield',
            name: 'delvery_clause', 
            fieldLabel: '交货条款',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 1
        }, {
            xtype:'textfield',
            name: 'responsible', 
            fieldLabel: '质保期',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            width: 120
        }, {
        	name: 'tpl_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	editable: false,
        	store: tplListStore,
        	fieldLabel: '合同模板',
            labelStyle: 'font-weight:bold',
            labelWidth: 70,
            labelAlign: 'right',
        	value: 13,
        	flex: 1.5
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
            xtype:'combobox',
            name: 'company', 
            fieldLabel: '下单方',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
        	displayField: 'text',
        	valueField: 'val',
        	triggerAction: 'all',
        	value: 0,
        	editable: false,
        	store: Ext.create('Ext.data.Store', {
                fields: ['text', 'val'],
                data: [
                    {"text": "Ophylink 通讯", "val": 0},
                    {"text": "Ophylink 软件", "val": 1}
                ]
            }),
            listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
        			if(newValue == 0){
        				Ext.getCmp('order_form').getForm().findField("tpl_id").setValue(1);
        			}else{
        				Ext.getCmp('order_form').getForm().findField("tpl_id").setValue(12);
        			}
        		}
        	},
            flex: 1.5
        }, {
            xtype:'textfield',
            name: 'description', 
            fieldLabel: '描述',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 3
        }, {
            xtype:'textfield',
            name: 'remark', 
            fieldLabel: '备注',
            labelStyle: 'font-weight:bold',
            labelWidth: 60,
            labelAlign: 'right',
            flex: 3
        }]
    }, {
        xtype: 'fieldcontainer',
        hidden: true,
        id: 'transfer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
        	id: 'transfer_type', 
        	name: 'transfer_type', 
        	xtype:'combobox',
        	displayField: 'text',
        	valueField: 'val',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	editable: false,
        	store: Ext.create('Ext.data.Store', {
                fields: ['text', 'val'],
                data: [
                    {"text": "修改", "val": "修改"},
                    {"text": "取消", "val": "取消"}
                ]
            }),
        	fieldLabel: '变更类别',
        	labelStyle: 'font-weight:bold',
        	labelWidth: 60,
        	listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
        			if(newValue == '修改'){
        				Ext.getCmp('itemsAddBtn').enable();
        				Ext.getCmp('itemsDeleteBtn').enable();
        			}else{
        				Ext.getCmp('itemsAddBtn').disable();
        				Ext.getCmp('itemsDeleteBtn').disable();
        			}
        		}
        	},
        	width: 120
        }, {
            xtype:'textfield',
            id: 'transfer_description',
            name: 'transfer_description',
            fieldLabel: '变更说明',
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
    	text: '审核',
    	id: 'orderReviewBtn',
    	handler: function(){
    		reviewOrder();
    	}
    }, {
        text: '提交',
        id: 'orderSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            var hand_number = Ext.getCmp('hand_number').getValue();
            var hand = Ext.getCmp('hand').getValue();
            //var receiver_id = Ext.getCmp('form_receiver_id').getValue();
            var customer_address_code = Ext.getCmp('form_customer_address_code').getValue();
            
            if(Ext.getCmp('operate').getValue() == 'transfer' && Ext.getCmp('transfer_description').getValue() == ''){
            	Ext.MessageBox.alert('错误', '请填写变更说明！');
            }else if(hand && hand_number == ''){
                Ext.MessageBox.alert('错误', '请填写订单编号！');
            }else if(customer_address_code == 0 || customer_address_code == null){
            	Ext.MessageBox.alert('错误', '请选择客户地址简码！');
            }else {
            	var transferErrInfo = '';
            	
            	if(Ext.getCmp('operate').getValue() == 'transfer'){
            		if(Ext.getCmp('transfer_type').getValue() == '取消'){
            			itemsStore.each(function(rec){
            				if(rec.get('items_qty_send') > 0){
            					transferErrInfo = '订单项存在已交货，不能整单取消！';
            				}
            			});
            		}else{
            			var itemsUpdate = itemsStore.getUpdatedRecords();
                        var itemsDelete = itemsStore.getRemovedRecords();
                        
                        for(var i = 0; i < itemsUpdate.length; i++){
                        	if(itemsUpdate[i].get('items_qty_send') > itemsUpdate[i].get('items_qty')){
                        		transferErrInfo = itemsUpdate[i].get('items_code') + '数量不能小于已交货数量！';
                        	}
                        }
                        
                        for(var i = 0; i < itemsDelete.length; i++){
                        	if(itemsDelete[i].get('items_qty_send') > 0){
                        		transferErrInfo = itemsDelete[i].get('items_code') + '已有交货记录，不能删除！';
                        	}
                        }
            		}
            	}
            	
            	if(transferErrInfo != ''){
            		Ext.MessageBox.alert('错误', transferErrInfo);
            	}else if(form.isValid()){
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
                        	var qty_send = rec.get('items_qty_send');
                        	var mpq = rec.get('items_mpq');
                        	var moq = rec.get('items_moq');
                        	
                        	if(qty > 0){
                        		if(qty < qty_send){
                            		valChk = false;
                            		errInfo = code + '下单数量[' + qty + ']小于已交货数量[' + qty_send + ']';
                            		
                            		break;
                            	}
                        	}else{
                        		valChk = false;
                        		errInfo = code + '数量错误，请填写下单数量!';
                        		
                        		break;
                        	}
                        }
                        
                        if(valChk){
                        	for(var i = 0; i < itemsInsertRecords.length; i++){
                            	var rec = itemsInsertRecords[i];
                            	
                            	var code = rec.get('items_code');
                            	var qty = rec.get('items_qty');
                            	var qty_send = rec.get('items_qty_send');
                            	var mpq = rec.get('items_mpq');
                            	var moq = rec.get('items_moq');
                            	
                            	if(qty > 0){
                            		if(qty < qty_send){
                                		valChk = false;
                                		errInfo = code + '下单数量[' + qty + ']小于已交货数量[' + qty_send + ']';
                                		
                                		break;
                                	}
                            	}else{
                            		valChk = false;
                            		errInfo = code + '数量错误，请填写下单数量!';
                            		
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
                                        params: {tax_name: form.findField('tax_name').getValue(), tax_rate: form.findField('tax_rate').getValue()},
                                        success: function(form, action) {
                                            var data = action.result;

                                            if(data.success){
                                                var errInfo = '';
                                                
                                                if(data.order_id){
                                                    // 当检查到数据有被修改时
                                                    //if(changeRowCnt > 0){
                                                        var changeRows = {
                                                    		order_id: data.order_id,
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
                                                        var operate = Ext.getCmp('operate').getValue();

                                                        Ext.Ajax.request({
                                                            url: homePath+'/public/erp/sale_order/edititems',
                                                            params: {json: json, operate: operate},
                                                            method: 'POST',
                                                            success: function(response, options) {
                                                                var data = Ext.JSON.decode(response.responseText);

                                                                if(!data.success){
                                                                    errInfo = data.info;
                                                                }
                                                            },
                                                            failure: function(response){
                                                                errInfo = '订单项保存提交失败';
                                                            }
                                                        });
                                                    //}
                                                }

                                                if(errInfo != ''){
                                                    Ext.MessageBox.alert('错误', errInfo);
                                                }else{
                                                    Ext.MessageBox.alert('提示', '保存成功');
                                                    form.reset();
                                                    orderWin.hide();
                                                    orderStore.loadPage(1);
                                                    Ext.getCmp('orderGrid').getSelectionModel().clearSelections();
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
        }
    }, {
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            orderWin.hide();
        }
    }]
});

var orderWin = Ext.create('Ext.window.Window', {
	title: '销售订单',
	id: 'orderWin',
	width: 1000,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [orderForm],
	tools: [{
		type:'help'
	}]
});