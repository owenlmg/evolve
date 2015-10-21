// 请购项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_transfer_type"},
             {name: "items_active"},
             {name: "items_order_id"},
             {name: "state",type: "int"},
             {name: "items_code"},
             {name: "items_type"},
             {name: "items_name"},
             {name: "items_mpq"},
             {name: "items_moq"},
             {name: "items_description"},
             {name: "items_supplier_code"},
             {name: "items_supplier_codename"},
             {name: "items_supplier_description"},
             {name: "items_remark"},
             {name: "items_warehouse_code"},
             {name: "items_price", type: "float"},
             {name: "items_qty", type: "float"},
             {name: "items_qty_receive", type: "float"},
             {name: "items_unit"},
             {name: "items_project_info"},
             {name: "items_model"},
             {name: "items_total", type: "float"},
             {name: "items_dept_id",type: "int"},
             {name: "items_req_item_id"},
             {name: "items_req_qty"},//多个数量逗号分隔
             {name: "items_req_number"},
             {name: "items_request_date",type: 'date',dateFormat: 'Y-m-d'},
             {name: "items_supplier"},
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
        url: homePath+'/public/erp/purchse_order/getorderitems'
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
        		
        		if(record.get('items_req_number') != '' 
        			&& (!Ext.getCmp('transfer').isHidden() && Ext.getCmp('transfer_type').getValue() == '取消') 
        			&& (e.field == 'items_req_number' 
        				|| e.field == 'items_code' 
    					|| e.field == 'items_qty' 
						|| e.field == 'items_name' 
						|| e.field == 'items_description')){
        			e.cancel = true;
        		}else if(record.get('items_req_number') != '' 
        			&& (!Ext.getCmp('transfer').isHidden() && Ext.getCmp('transfer_type').getValue() == '修改') 
        			&& e.field == 'items_code'){
        			e.cancel = true;
        		}else if(record.get('items_code') != '' && (e.field == 'items_name' || e.field == 'items_description')){
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
	        url: homePath + '/public/erp/purchse_order/getprint/id/' + id + '/tpl_id/' +　tpl_id,
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
    	text: '选择采购申请',
    	id: 'itemsSelectBtn',
    	iconCls: 'icon-list',
    	handler: function(){
    		reqWin.show();
    		reqStore.removeAll();
    	}
    }, {
        text: '添加',
        id: 'itemsAddBtn',
        iconCls: 'icon-add',
        handler: function(){
            itemsRowEditing.cancelEdit();
            
            var r = Ext.create('Items', {
                items_active: true,
                items_qty: 1,
                items_qty: 0,
                items_unit: '个',
                items_price: 0,
                items_line_total: 0,
                items_dept_id: 1,
                items_warehouse_code: '103',
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
            	if(Ext.getCmp('operate').getValue() == 'transfer' && selection[0].get('items_qty_receive') > 0){
            		Ext.MessageBox.alert('错误', '当前记录已有收货，不能删除！');
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
        		
        		if(!checked && rec.get('items_qty_receive') > 0){
        			Ext.MessageBox.show({
                        title: '错误',
                        msg: '当前记录已有收货，不能取消！',
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
    	text: '申请',
    	align: 'center',
    	dataIndex: 'items_req_number',
    	renderer: function(val, cellmeta, record, rowIndex){
    		if(val != ''){
    			cellmeta.tdAttr = 'data-qtip="' + val + '"';
    			return '<img src="'+homePath+'/public/images/icons/ok.png"></img>';
    		}
    	},
    	width: 50
    }, {
        text: '物料号',
        dataIndex: 'items_code',
        editor: new Ext.form.field.ComboBox({
            displayField: 'text',
            valueField: 'text',
            triggerAction: 'all',
            lazyRender: true,
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
                        	Ext.MessageBox.alert('错误', '物料号重复！');
                        	sel.clearValue();
                        	itemsRowEditing.cancelEdit();
                        }else{
                    		var index = codeStore.findExact('text',newValue);

                    		var name = '';
                    		var mpq = '';
                    		var moq = '';
                    		var description = '';
                    		
                            if (index != -1){
                            	rs = codeStore.getAt(index).data;

                            	name = rs.name;
                            	mpq = rs.mpq;
                            	moq = rs.moq;
                            	description = rs.description;
                            }

                            // 选择物料号，自动填充名称、描述
                            rec.set('items_name', name);
                            rec.set('items_mpq', mpq);
                            rec.set('items_moq', moq);
                        	rec.set('items_description', description);
                        }
            		}
                }
            }
        }),
        width: 120
    }, {
        text: '名称',
        editor: 'textfield',
        dataIndex: 'items_name',
        summaryType: 'count',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '项数：' + ((value === 0 || value > 1) ? value + ' 项' : '1 项');
        },
        width: 120
    }, {
        text: '描述',
        editor: 'textfield',
        dataIndex: 'items_description',
        width: 180
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
                        		var supplier_id = Ext.getCmp('order_form').getForm().findField("supplier_id").getValue();
                            	var date = Ext.util.Format.date(Ext.getCmp('order_form').getForm().findField("order_date").getValue(), 'Y-m-d');
                            	
                            	Ext.Ajax.request({
                                    url: homePath+'/public/erp/warehouse_pricelist/getprice',
                                    params: {code: rec.get('items_code'), supplier_id: supplier_id, fix: 0, date: date, qty: newValue},
                                    method: 'POST',
                                    success: function(response, options) {
                                    	result = Ext.JSON.decode(response.responseText);
                                    	
                                    	// 获取价格
                                    	var price = result.price['price'];

                                    	rec.set('items_price', price);
                                    	
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
    	text: '已收货',
    	align: 'center',
    	dataIndex: 'items_qty_receive',
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
        text: '单价',
        editor: new Ext.form.NumberField({  
            decimalPrecision: 8,
            minValue: 0
        }),
        renderer: function(value, meta, rec){
        	return setMoney(value, getCurrentCurrency());
        },
        dataIndex: 'items_price',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '合计：';
        },
        width: 100
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
        text: '需求日期',//多个申请时，取最早日期
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
        text: '申请单号',
        dataIndex: 'items_req_number',
        editor: 'textfield',
        width: 120
    }, {
        text: '供应商产品型号',
        editor: 'textfield',
        dataIndex: 'items_supplier_code',
        width: 100
    }, {
        text: '供应商产品名称',
        editor: 'textfield',
        dataIndex: 'items_supplier_codename',
        width: 100
    }, {
        text: '供应商产品描述',
        editor: 'textfield',
        dataIndex: 'items_supplier_description',
        width: 100
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
        text: '需求部门',
        dataIndex: 'items_dept_id',
        renderer: deptRender,
        editor: new Ext.form.field.ComboBox({
            editable: false,
            displayField: 'name',
            valueField: 'id',
            triggerAction: 'all',
            lazyRender: true,
            store: deptStore,
            queryMode: 'local'
        }),
        width: 120
    }, {
        text: '项目信息',
        editor: 'textfield',
        dataIndex: 'items_project_info',
        width: 200
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 200
    }]
});

// 设置价格
function setItemPrice(index, code, qty, supplier_id, date, currency){
	Ext.Ajax.request({
        url: homePath+'/public/erp/warehouse_pricelist/getprice',
        params: {code: code, supplier_id: supplier_id, fix: 0, date: date, qty: qty, currency: currency},
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
	
	var supplier_id = Ext.getCmp('order_form').getForm().findField("supplier_id").getValue();
	var currency = Ext.getCmp('order_form').getForm().findField("currency").getValue();
	var date = Ext.util.Format.date(Ext.getCmp('order_form').getForm().findField("order_date").getValue(), 'Y-m-d');
	
	var items = itemsStore.data.items;
	var items_insert = [];
	
	for(var i = 0; i < items.length; i++){
		if(items[i].get('items_code')){
			var code = items[i].get('items_code');
			var qty = items[i].get('items_qty');
			
			setItemPrice(i, code, qty, supplier_id, date, currency);
		}
	}
	
	Ext.Msg.hide();
}

var orderForm = Ext.create('Ext.form.Panel', {
	id: 'order_form',
	border: 0,
    url: homePath+'/public/erp/purchse_order/editorder',
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
        	listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
            		if(newValue != oldValue){
            			index = typeListStore.findExact('id',newValue);
            	        if (index != -1){
            	            rs = typeListStore.getAt(index).data;
            	            
            	            Ext.getCmp('order_form').getForm().findField("chk_package_qty").setValue(rs.chk_package_qty);
            	        }
            		}
                }
        	},
           	width: 160
        }, {
        	name: 'chk_package_qty',
        	xtype: 'hiddenfield',
        	width: 30
        }, {
        	name: 'buyer_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	editable: false,
        	allowBlank: false,
        	store: buyerListStore,
        	fieldLabel: '采购员',
        	labelWidth: 60,
        	width: 160
        }, {
        	name: 'receiver_id',
        	id: 'form_receiver_id',
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	editable: false,
        	store: receiverListStore,
        	fieldLabel: '收货人',
        	labelWidth: 60,
        	value: 1,
        	listeners: {
            	change: function( sel, newValue, oldValue, eOpts ){
            		if(newValue != oldValue){
            			if(newValue == 0){
            				Ext.getCmp('form_receiver_id').hide();
            				Ext.getCmp('form_customer_address_code').show();
            				Ext.getCmp('form_customer_address_code').clearValue();
            			}
            		}
                }
            },
        	width: 160
        }, {
        	name: 'customer_address_code',
        	id: 'form_customer_address_code',
        	hidden: true,
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
            		if(newValue != oldValue){
            			if(newValue == 0){
            				Ext.getCmp('form_receiver_id').show();
            				Ext.getCmp('form_customer_address_code').hide();
            				Ext.getCmp('form_receiver_id').clearValue();
            			}
            		}
                }
            },
        	width: 270
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
                            var text = record.get(combo.displayField)/*.toLowerCase()*/;  
                            return regExp.test(text); 
                        });
                        combo.expand();  
                        return false;
                    }
                },
            	change: function( sel, newValue, oldValue, eOpts ){
            		if(this.up('form').getForm().findField('operate').getValue() != ''){
            			supplierContactStore.load({
                            params: {
                                partner_id: newValue
                            },
                            callback: function(records, operation, success) {
                                var index = supplierStore.findExact('id', newValue);
                                
                                if(index != -1){
                                    var currency = supplierStore.getAt(index).data.currency;
                                    
                                    Ext.getCmp('order_form').getForm().findField("currency").setValue(currency);
                                    Ext.getCmp('order_form').getForm().findField("supplier_contact_id").setValue(records[0].get('id'));

                                    if(Ext.getCmp('operate').getValue() != ''){
                                        refreshPrice();
                                    }
                                }
                            }
                        });
            		}else{
            		    supplierContactStore.load({
                            params: {
                                partner_id: newValue
                            }
                        });
            		}
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
        	width: 120
        }, {
        	name: 'price_tax',
        	xtype: 'checkboxfield',
        	fieldLabel: '价格含税',
        	checked: false,
        	labelWidth: 70,
        	width: 100
        }, {
        	name: 'supplier_contact_id', 
        	xtype:'combobox',
        	displayField: 'name',
        	valueField: 'id',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	afterLabelTextTpl: required,
        	editable: false,
        	store: supplierContactStore,
        	fieldLabel: '联系人',
        	labelWidth: 60,
        	allowBlank: false,
        	width: 170
        }, {
        	name: 'request_date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
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
            name: 'manufacture', 
            fieldLabel: '产地及品牌',
            labelStyle: 'font-weight:bold',
            labelWidth: 70,
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
        	value: 1,
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
                    {"text": "Ophylink软件", "val": 1}
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
        				Ext.getCmp('itemsSelectBtn').enable();
        			}else{
        				Ext.getCmp('itemsAddBtn').disable();
        				Ext.getCmp('itemsDeleteBtn').disable();
        				Ext.getCmp('itemsSelectBtn').disable();
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
    buttons: [/*{
    	xtype: 'textfield',
    	id: 'operate_type'
    }, */{
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
            var receiver_id = Ext.getCmp('form_receiver_id').getValue();
            var customer_address_code = Ext.getCmp('form_customer_address_code').getValue();
            
            if(Ext.getCmp('operate').getValue() == 'transfer' && Ext.getCmp('transfer_description').getValue() == ''){
            	Ext.MessageBox.alert('错误', '请填写变更说明！');
            }else if(hand && hand_number == ''){
                Ext.MessageBox.alert('错误', '请填写订单编号！');
            }else if((receiver_id == 0 || receiver_id == null) && (customer_address_code == 0 || customer_address_code == null)){
            	Ext.MessageBox.alert('错误', '请选择收货人/客户地址简码！');
            }else {
            	var transferErrInfo = '';
            	
            	if(Ext.getCmp('operate').getValue() == 'transfer'){
            		if(Ext.getCmp('transfer_type').getValue() == '取消'){
            			itemsStore.each(function(rec){
            				if(rec.get('items_qty_receive') > 0){
            					transferErrInfo = '订单项存在已收货，不能整单取消！';
            				}
            			});
            		}else{
            			var itemsUpdate = itemsStore.getUpdatedRecords();
                        var itemsDelete = itemsStore.getRemovedRecords();
                        
                        for(var i = 0; i < itemsUpdate.length; i++){
                        	if(itemsUpdate[i].get('items_qty_receive') > itemsUpdate[i].get('items_qty')){
                        		transferErrInfo = itemsUpdate[i].get('items_code') + '数量不能小于已收货数量！';
                        	}
                        }
                        
                        for(var i = 0; i < itemsDelete.length; i++){
                        	if(itemsDelete[i].get('items_qty_receive') > 0){
                        		transferErrInfo = itemsDelete[i].get('items_code') + '已有收货记录，不能删除！';
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
                    	var chk_package_qty = Ext.getCmp('order_form').getForm().findField("chk_package_qty").getValue();
                        
                        for(var i = 0; i < itemsUpdateRecords.length; i++){
                        	var rec = itemsUpdateRecords[i];
                        	var code = rec.get('items_code');
                        	var qty = rec.get('items_qty');
                        	var qty_receive = rec.get('items_qty_receive');
                        	var mpq = rec.get('items_mpq');
                        	var moq = rec.get('items_moq');
                        	
                        	if(qty <= 0){
                        		valChk = false;
                        		errInfo = code + '数量错误，请填写下单数量!';
                        		
                        		break;
                        	}else if(chk_package_qty == 1){
                        		if(mpq > 0 && qty % mpq != 0){
                            		valChk = false;
                            		errInfo = code + '下单数量[' + qty + ']不满足最小包装量[' + mpq + ']';
                            		
                            		break;
                            	}else if(moq > 0 && qty < moq){
                            		valChk = false;
                            		errInfo = code + '下单数量[' + qty + ']不满足最小订单量[' + moq + ']';
                            		
                            		break;
                            	}else if(qty < qty_receive){
                            		valChk = false;
                            		errInfo = code + '下单数量[' + qty + ']小于已收货数量[' + qty_receive + ']';
                            		
                            		break;
                            	}
                        	}
                        }
                        
                        if(valChk){
                        	for(var i = 0; i < itemsInsertRecords.length; i++){
                            	var rec = itemsInsertRecords[i];
                            	var code = rec.get('items_code');
                            	var qty = rec.get('items_qty');
                            	var qty_receive = rec.get('items_qty_receive');
                            	var mpq = rec.get('items_mpq');
                            	var moq = rec.get('items_moq');
                            	
                            	if(qty <= 0){
                            		valChk = false;
                            		errInfo = code + '数量错误，请填写下单数量!';
                            		
                            		break;
                            	}else if(chk_package_qty == 1){
                            		if(mpq > 0 && qty % mpq != 0){
                                		valChk = false;
                                		errInfo = code + '下单数量[' + qty + ']不满足最小包装量[' + mpq + ']';
                                		
                                		break;
                                	}else if(moq > 0 && qty < moq){
                                		valChk = false;
                                		errInfo = code + '下单数量[' + qty + ']不满足最小订单量[' + moq + ']';
                                		
                                		break;
                                	}else if(qty < qty_receive){
                                		valChk = false;
                                		errInfo = code + '下单数量[' + qty + ']小于已收货数量[' + qty_receive + ']';
                                		
                                		break;
                                	}
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
                                                            url: homePath+'/public/erp/purchse_order/edititems',
                                                            params: {json: json, operate: operate},
                                                            method: 'POST',
                                                            success: function(response, options) {
                                                                var data = Ext.JSON.decode(response.responseText);

                                                                // 当保存采购项目出错，记录错误信息
                                                                if(!data.success){
                                                                    errInfo = data.info;
                                                                }
                                                            },
                                                            failure: function(response){
                                                                errInfo = '采购项目保存提交失败';
                                                            }
                                                        });
                                                    //}
                                                }

                                                // 当保存采购项目出错，提示错误信息
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

// 请购单窗口
var orderWin = Ext.create('Ext.window.Window', {
	title: '采购订单',
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
	}]/*,
	listeners: {
		beforeshow: function( win, e ){
			
		}
	}*/
});