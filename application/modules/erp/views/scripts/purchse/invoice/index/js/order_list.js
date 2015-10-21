Ext.define('Order', {
    extend: 'Ext.data.Model',
	    fields: [{name: "order_id"},
	             {name: "order_type_id"},
	             {name: "order_type_name"},
	             {name: "order_number"},
	             {name: "order_date"},
	             {name: "order_buyer_id"},
	             {name: "order_buyer_name"},
	             {name: "order_qty"},
	             {name: "order_price_tax"},
	             {name: "order_currency"},
	             {name: "order_currency_rate"},
	             {name: "order_tax_id"},
	             {name: "order_tax_name"},
	             {name: "order_tax_rate"},
	             {name: "order_receiver"},
	             {name: "order_remark"},
	             {name: "order_supplier_id"},
	             {name: "order_supplier_code"},
	             {name: "order_supplier_name"},
	             {name: "order_supplier_contact"},
	             {name: "id"},
	             {name: "request_date"},
	             {name: "qty"},
	             {name: "price",type:"float"},
	             {name: "qty_invoice"},
	             {name: "qty_receive"},
	             {name: "qty_left"},
	             {name: "unit"},
	             {name: "code"},
	             {name: "name"},
	             {name: "description"},
	             {name: "remark"},
	             {name: "supplier_code"},
	             {name: "supplier_codename"},
	             {name: "supplier_description"},
	             {name: "warehouse_code"}]
});

var orderStore = Ext.create('Ext.data.Store', {
    model: 'Order',
    groupField: 'order_number',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_order/getinvoiceorderlist'
    },
    listeners: {
    	beforeload: loadOrder
    }
});

function loadOrder(){
	var key = Ext.getCmp('search_order_key').getValue();
	var supplier_id = Ext.getCmp('search_order_supplier_id').getValue();
	var currency = Ext.getCmp('search_order_currency').getValue();
    
	Ext.apply(orderStore.proxy.extraParams, {
		invoice: 1,
		key: key,
		supplier_id: supplier_id,
		currency: currency
    });
};

var groupingFeature = Ext.create('Ext.grid.feature.Grouping',{
	groupHeaderTpl: '{name}：{rows.length} 项 [采购员：{[values.rows[0].data.order_buyer_name]}] [采购类别：{[values.rows[0].data.order_type_name]}]',
    hideGroupedHeader: true
});

var orderListRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

function addInvoiceRow(items){
	var insertCnt = 0;
	
	for(var i = 0; i < items.length; i++){
		rec = items[i];
		
		var r = Ext.create('Items', {
			items_order_item_id: rec.get('id'),
			items_order_id: rec.get('order_id'),
			items_order_number: rec.get('order_number'),
			items_order_date: rec.get('order_date'),
			items_order_currency: rec.get('order_currency'),
			items_order_supplier: rec.get('order_supplier_code'),
			items_order_currency: rec.get('order_currency'),
			items_order_currency_rate: rec.get('order_currency_rate'),
			items_order_tax_id: rec.get('order_tax_id'),
			items_order_tax_name: rec.get('order_tax_name'),
			items_order_tax_rate: rec.get('order_tax_rate'),
			items_code: rec.get('code'),
	        items_qty: rec.get('qty_invoice'),
			items_price: rec.get('price'),
			items_price_tax: rec.get('order_price_tax'),
			items_name: rec.get('name'),
			items_description: rec.get('description'),
	        items_unit: rec.get('unit')
	    });

	    itemsStore.insert(0, r);
	    
	    insertCnt++;
	}
	
	return insertCnt;
}

// 复制数量
function copyOrderQty(){
    var selection = orderGrid.getView().getSelectionModel().getSelection();
    
    var rec = selection[0];
    
    rec.set('qty_invoice', rec.get('qty_left'));
}

// 采购订单列表
var orderGrid = Ext.create('Ext.grid.Panel', {
	border: 0,
    id: 'orderGrid',
    columnLines: true,
    store: orderStore,
    //selType: 'checkboxmodel',
    features: [groupingFeature],
    plugins: orderListRowEditing,
    tbar: [{
    	xtype: 'hiddenfield',
    	id: 'search_order_supplier_id'
    }, {
    	xtype: 'hiddenfield',
    	id: 'search_order_currency'
    }, {
    	xtype: 'textfield',
        id: 'search_order_key',
        emptyText: '关键字...',
        listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	orderStore.load();
                }
            }
        }
    }, {
    	text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	orderStore.load();
        }
    }, {
        text: '选择',
        iconCls: 'icon-ok',
        handler: function(){
        	orderListRowEditing.cancelEdit();
        	
        	var qtyChkInfo = '';// 检查收货数量
        	var itemsInsert = [];
        	
        	// 检查收货数量
        	orderStore.each(function(rec){
        		if(qtyChkInfo == ''){
        			var qty_invoice = Number(rec.get('qty_invoice'));
            		var qty_left = Number(rec.get('qty_left'));
            		var qty_receive = Number(rec.get('qty_receive'));
            		var code = rec.get('code');
            		
            		if(qty_invoice > 0){
            			if(code != ''){
            				if(qty_invoice > qty_receive){
            					qtyChkInfo = rec.get('code') + "已收货数量不足：" + qty_invoice + ' > ' + qty_receive;
            				}else if(qty_invoice > qty_left){
            					qtyChkInfo = rec.get('code') + "未开票数量不足：" + qty_invoice + ' > ' + qty_left;
            				}
            			}else if(code == '' && qty_invoice > qty_left){
            				qtyChkInfo = rec.get('name') + "未开票数量不足：" + qty_invoice + ' > ' + qty_left;
            			}
            			
            			if(qtyChkInfo == ''){
            				itemsInsert.push(rec);
            			}
            		}
        		}
        	});
        	
        	// 数量检查结果
        	if(qtyChkInfo == ''){
        		if(itemsInsert.length > 0){
        			// 清除已添加项
            		for(var i = 0; i < itemsInsert.length; i++){
            			recInsert = itemsInsert[i];
            			
            			itemsStore.each(function(rec) {
            				if(recInsert.get('code') == null){
            					if (rec.get('items_name') == recInsert.get('name') && rec.get('items_description') == recInsert.get('description')) {
                    		    	itemsStore.remove(rec);
                    		    }
            				}else{
            					if (rec.get('items_order_item_id') == recInsert.get('id')) {
                    		    	itemsStore.remove(rec);
                    		    }
            				}
                		});
            		}
            		
            		// 添加行
            		var qtyAdded = addInvoiceRow(itemsInsert);
                	
            		if(qtyAdded > 0){
            			orderWin.hide();
                		
                		itemsRowEditing.startEdit(0, 0);
            		}else{
            			Ext.MessageBox.alert('错误', '没有选择任何项目，请填写收货数量！');
            		}
        		}else{
        			Ext.MessageBox.alert('错误', '请填写收货数量！');
        		}
        	}else{
        		Ext.MessageBox.alert('错误', qtyChkInfo);
        	}
        }
    }, '->', {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
       	 orderStore.reload();
        }
    }],
    columns: [{
        text: 'ID',
        dataIndex: 'id',
        hidden: true,
        width: 50
    }, {
        text: '订单ID',
        dataIndex: 'order_id',
        hidden: true,
        width: 50
    }, {
        text: '物料号',
        dataIndex: 'code',
        width: 100
    }, {
        text: '订单数量',
        dataIndex: 'qty',
        width: 80
    }, {
        text: '收货数量',
        dataIndex: 'qty_receive',
        width: 80
    }, {
        text: '未清数量',
        dataIndex: 'qty_left',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '添加',
        align: 'center',
        renderer: function(val, meta, record){
            meta.style = 'font-weight: bold';
            
            return '<div style="cursor:pointer;" onclick="copyOrderQty();" data-qtip="全部添加" class="qtip-target"><img src="'+homePath+'/public/images/icons/select.gif"></img></div>';
        },
        width: 60
    }, {
        text: '收货数量',
        dataIndex: 'qty_invoice',
        editor: 'numberfield',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #FFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '名称',
        dataIndex: 'name',
        flex: 1
    }, {
        text: '描述',
        dataIndex: 'description',
        flex: 3
    }, {
        text: '备注',
        dataIndex: 'remark',
        flex: 1
    }]
});

// 采购订单窗口
var orderWin = Ext.create('Ext.window.Window', {
	title: '采购订单列表',
	id: 'orderWin',
	height: 400,
	width: 900,
	modal: true,
	constrain: true,
	maximizable: true,
	closeAction: 'hide',
	layout: 'fit',
	tools: [{
	    type: 'refresh',
	    tooltip: 'Refresh',
	    scope: this,
	    handler: function(){orderStore.reload();}
	}],
	items: [orderGrid]
});