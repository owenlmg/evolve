Ext.define('Req', {
    extend: 'Ext.data.Model',
    fields: [{name: "req_id"},
             {name: "req_item_id"},
             {name: "req_qty"},
             {name: "req_number"},
             {name: "req_dept_id",type: "int"},
             {name: "req_dept"},
             {name: "req_release_time",type: 'date',dateFormat: 'timestamp'},
             {name: "req_remark"},
             {name: "req_reason"},
             {name: "req_type_id",type: "int"},
             {name: "req_type"},
             {name: "id"},
             {name: "active"},
             {name: "order_flag"},
             {name: "code"},
             {name: "qty"},
             {name: "qty_order"},
             {name: "qty_left"},
             {name: "name"},
             {name: "model"},
             {name: "description"},
             {name: "remark"},
             {name: "project_info"},
             {name: "customer_address"},
             {name: "customer_aggrement"},
             {name: "supplier"},
             {name: "date_req",type: 'date',dateFormat: 'Y-m-d'},
             {name: "unit"},
             {name: "price"},
             {name: "line_total"},
             {name: "create_time",type: 'date',dateFormat: 'timestamp'},
             {name: "update_time",type: 'date',dateFormat: 'timestamp'},
             {name: "creater"},
             {name: "updater"}]
});

var reqStore = Ext.create('Ext.data.Store', {
    model: 'Req',
    groupField: 'req_number',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/getreqitemslist'
    },
    listeners: {
    	beforeload: loadReq
    }
});

function loadReq(){
	var key = Ext.getCmp('search_req_key').getValue();
    
	Ext.apply(reqStore.proxy.extraParams, {
		key: key
    });
};

var groupingFeature = Ext.create('Ext.grid.feature.Grouping',{
	groupHeaderTpl: '{name}：{rows.length} 项 [申请部门：{[values.rows[0].data.req_dept]}] [采购类别：{[values.rows[0].data.req_type]}] [申请人：{[values.rows[0].data.creater]}]',
    hideGroupedHeader: true,
    //startCollapsed: true,
    id: 'reqListGrouping'
});

var reqListRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 检查物料列表是否已包含当前物料
function checkItemContain(array, item){
	for(var i = 0; i < array.length; i++){
		if(item.get('code') != null && item.get('code') != ''){
			if(array[i].get('code') == item.get('code')){
				return true;
			}
		}else{
			if(array[i].get('name') == item.get('name') && array[i].get('description') == item.get('description')){
				return true;
			}
		}
	}
	
	return false;
}

// 设置物料列表中的重复项
function setItemContain(array, item){
	for(var i = 0; i < array.length; i++){
		if(array[i].get('code') == item.get('code')){
			array[i].set('qty_order', Number(array[i].get('qty_order')) + Number(item.get('qty_order')));
			array[i].set('req_item_id', array[i].get('req_item_id') + ',' + item.get('id'));
			array[i].set('req_qty', array[i].get('req_qty') + ',' + item.get('req_qty'));
			
			if(array[i].get('req_number').indexOf(item.get('req_number')) == -1){
			    array[i].set('req_number', array[i].get('req_number') + ',' + item.get('req_number'));
			}
		}
	}
	
	return array;
}

Array.prototype.in_array = function(e)  
{  
    for(i=0;i<this.length;i++)  
    {  
        if(this[i] == e)  
        return true;  
    }  
    return false;  
}

// 插入新行
function insertOrderRow(item, supplier_id, date, currency){
	var code = item.get('code');
	var unit = item.get('unit');
	var name = item.get('name');
	var description = item.get('description');
	var qty = item.get('qty_order');
	var dept_id = item.get('req_dept_id');
	var date_req = item.get('date_req');
	var req_item_id = item.get('req_item_id');
	var req_qty = item.get('req_qty');
	var req_number = item.get('req_number');
	var customer_address = item.get('customer_address');
	var customer_address = item.get('customer_address');
	var customer_aggrement = item.get('customer_aggrement');
	
	Ext.Ajax.request({
        url: homePath+'/public/erp/warehouse_pricelist/getprice',
        params: {code: code, supplier_id: supplier_id, fix: 0, date: date, qty: qty, currency: currency},
        method: 'POST',
        success: function(response, options) {
        	result = Ext.JSON.decode(response.responseText);
        	
        	// 获取申请ID及下单数量
        	var price = result.price['price'];
        	
        	var remark = item.get('remark');
        	
        	if(customer_address != '' && customer_address != null){
        	    remark += '客户收件人地址简码: ' + customer_address;
        	}
        	
        	if(customer_aggrement != '' && customer_aggrement != null){
        	    remark += ' ' + customer_address;
        	}

        	var r = Ext.create('Items', {
    			items_req_item_id: req_item_id,
    			items_req_qty: req_qty,
    			items_req_number: req_number,
    			items_code: code,
    			items_name: name,
    			items_description: description,
                items_active: true,
                items_qty: qty,
                items_unit: unit,
                items_remark: remark,
                items_price: price,
                items_dept_id: dept_id,//多部门
                items_request_date: date_req//多日期
            });

            itemsStore.insert(itemsStore.getCount(), r);
        },
        failure: function(response){
        	Ext.MessageBox.alert('错误', code + '价格获取提交失败');
        }
    });
}

// 根据价格清单获取物料价格
function addOrderRow(items){
	var items_insert = [];
	
	for(var i = 0; i < items.length; i++){
		if(items[i].get('qty_order') > 0){
			items[i].set('req_qty', items[i].get('qty_order'));
			items[i].set('req_item_id', items[i].get('id'));
			
			if(checkItemContain(items_insert, items[i])){
				setItemContain(items_insert, items[i]);
			}else{
				items_insert.push(items[i]);
			}
		}
	}
	
	var supplier_id = Ext.getCmp('order_form').getForm().findField("supplier_id").getValue();
	var currency = Ext.getCmp('order_form').getForm().findField("currency").getValue();
	var date = Ext.util.Format.date(Ext.getCmp('order_form').getForm().findField("order_date").getValue(), 'Y-m-d');
	
	for(var i = 0; i < items_insert.length; i++){
		insertOrderRow(items_insert[i], supplier_id, date, currency);
	}
	
	return items_insert.length;
}

// 复制数量
function copyOrderQty(){
    var selection = reqGrid.getView().getSelectionModel().getSelection();
    
    var rec = selection[0];
    
    rec.set('qty_order', rec.get('qty_left'));
}

// 采购申请列表
var reqGrid = Ext.create('Ext.grid.Panel', {
	border: 0,
    id: 'reqGrid',
    columnLines: true,
    store: reqStore,
    //selType: 'checkboxmodel',
    features: [groupingFeature],
    plugins: reqListRowEditing,
    tbar: [{
    	xtype: 'textfield',
        id: 'search_req_key',
        emptyText: '关键字...',
        listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	reqStore.load();
                }
            }
        }
    }, {
    	xtype: 'splitbutton',
    	text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	reqStore.load();
        },
        menu: [{
        	text: '导出',
        	iconCls: 'icon-export',
        	handler: function(){
        		var key = Ext.getCmp('search_req_key').getValue();
        		window.open(homePath+'/public/erp/purchse_req/getreqitemslist/option/csv/key/' + key + '/type/csv');
        	}
        }]
    }, {
        text: '选择',
        iconCls: 'icon-ok',
        handler: function(){
        	reqListRowEditing.cancelEdit();

        	var items = reqStore.data.items;

        	var qtyChkInfo = '';
        	
        	// 添加行前：1、清除已添加物料所在行，2、如清除后已添加当前物料，则合并数量
        	for(var i = 0; i < items.length; i++){
        		var qty_order = Number(items[i].get('qty_order'));
        		var qty_left = Number(items[i].get('qty_left'));
        		
        		if(qty_order > 0){
        			if(qty_order > qty_left){
        				var name = items[i].get('code');
        				if(name == null){
        					name = items[i].get('name');
        				}
        				
        				qtyChkInfo = name + " 剩余数量不足：" + qty_order + ' > ' + qty_left;
        				break;
        			}

        			itemsStore.each(function(rec) {
        				if (items[i].get('code') == null || items[i].get('code') == ''){
        					if (rec.get('items_name') == items[i].get('name') && rec.get('items_description') == items[i].get('description')) {
                		    	itemsStore.remove(rec);
                		    }
        				}else{
        					if (rec.get('items_code') == items[i].get('code')) {
                		    	itemsStore.remove(rec);
                		    }
        				}
            		});
        		}
        	}
        	
        	if(qtyChkInfo == ''){
        		// 添加行
            	var qtyAdded = addOrderRow(items);
            	
            	if(qtyAdded > 0){
            		reqWin.hide();
            		
            		itemsRowEditing.startEdit(itemsStore.getCount(), 0);
            	}else{
            		Ext.MessageBox.alert('错误', '没有选择任何项目，请填写下单数量！');
            	}
        	}else{
        		Ext.MessageBox.alert('错误', qtyChkInfo);
        	}
        }
    }, '->', {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
       	 reqStore.reload();
        }
    }],
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(!record.get('active') || record.get('order_flag') == 1){
                return 'gray-row';
            }
        }
    },
    columns: [{
        text: 'ID',
        dataIndex: 'id',
        hidden: true,
        width: 50
    }, {
        text: '申请ID',
        dataIndex: 'req_id',
        hidden: true,
        width: 50
    }, {
        text: '申请项ID',
        dataIndex: 'req_item_id',
        hidden: true,
        width: 50
    }, {
        text: '物料号',
        dataIndex: 'code',
        width: 100
    }, {
        text: '名称',
        dataIndex: 'name',
        width: 100
    }, {
        text: '申请数量',
        dataIndex: 'qty',
        width: 80
    }, {
        text: '剩余数量',
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
        text: '下单数量',
        dataIndex: 'qty_order',
        editor: 'numberfield',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #FFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '单位',
        align: 'center',
        dataIndex: 'unit',
        width: 50
    }, {
        text: '描述',
        dataIndex: 'description',
        width: 200
    }, {
        text: '客户收件人地址简码',
        dataIndex: 'customer_address',
        width: 140
    }, {
        text: '备注',
        dataIndex: 'remark',
        width: 200
    }, {
        text: '项目信息',
        dataIndex: 'project_info',
        width: 200
    }, {
        text: '申请事由',
        dataIndex: 'req_reason',
        width: 200
    }, {
        text: '申请备注',
        dataIndex: 'req_remark',
        width: 200
    }]
});

// 采购申请窗口
var reqWin = Ext.create('Ext.window.Window', {
	title: '采购申请列表',
	id: 'reqWin',
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
	    handler: function(){reqStore.reload();}
	}],
	items: [reqGrid]
});