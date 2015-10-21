Ext.define('Select', {
    extend: 'Ext.data.Model',
    fields: [{name: "type"},
             {name: "code"},
             {name: "unit"},
             {name: "name"},
             {name: "description"},
             {name: "customer_code"},
             {name: "customer_description"},
             {name: "product_type"},
             {name: "product_series"}]
});

var selectStore = Ext.create('Ext.data.Store', {
    model: 'Select',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_price/select'
    },
    listeners: {
    	beforeload: function(store){
    		var type = Ext.getCmp('search_select_type').getValue();
    		var key = Ext.getCmp('search_select_key').getValue();
            
            Ext.apply(store.proxy.extraParams, {
                key: key,
                type: type
            });
    	}
    }
});

var selectGrid = Ext.create('Ext.grid.Panel', {
	border: 0,
    id: 'selectGrid',
    columnLines: true,
    store: selectStore,
    //selType: 'checkboxmodel',
    tbar: [{
    	xtype: 'combobox',
        id: 'search_select_type',
        value: 'catalog',
        editable: false,
        displayField: 'text',
        valueField: 'val',
        width: 80,
        store: Ext.create('Ext.data.Store', {
            fields: ['text', 'val'],
            data: [
                {"text": "物料代码", "val": 'material'},
                {"text": "内部型号", "val": 'catalog'}
            ]
        })
    }, {
    	xtype: 'textfield',
        id: 'search_select_key',
        emptyText: '关键字...',
        listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	selectStore.load();
                }
            }
        }
    }, {
    	text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	selectStore.load();
        }
    }],
    columns: [{
        text: '物料号/内部型号',
        dataIndex: 'code',
        width: 150
    }, {
        text: '名称',
        dataIndex: 'name',
        width: 120
    }, {
        text: '产品类别',
        dataIndex: 'product_type',
        width: 100
    }, {
        text: '产品系列',
        dataIndex: 'product_series',
        width: 100
    }, {
        text: '单位',
        align: 'center',
        dataIndex: 'unit',
        width: 50
    }, {
        text: '描述',
        dataIndex: 'description',
        flex: 2
    }, {
        text: '客户型号',
        dataIndex: 'customer_code',
        width: 120
    }, {
        text: '客户产品描述',
        dataIndex: 'customer_description',
        flex: 2
    }, {
    	xtype: 'actioncolumn',
        width: 30,
        align: 'center',
        sortable: false,
        menuDisabled: true,
        items: [{
            icon: homePath + '/public/images/icons/add.png',
            tooltip: '选择添加',
            scope: this,
            handler: function(grid, rowIndex){
            	var store = grid.getStore();
            	var rec = store.getAt(rowIndex);
            	
            	var include = false;
            	
            	itemsStore.each(function(record){
            		if(record.get('items_type') == rec.get('type') && record.get('items_code') == rec.get('code')){
            			include = true;
            		}
            	});
            	
            	if(include){
            		Ext.MessageBox.alert('错误', '该项已存在，请勿重复添加！');
            	}else{
            		var items_id = 1;
            		
            		if(itemsStore.getCount() > 0){
            			items_id = itemsStore.getAt(itemsStore.getCount() - 1).get('items_id') + 1;
            		}
            		
            		var r = Ext.create('Items', {
            			items_id: items_id,
            			items_type: rec.get('type'),
            			items_code: rec.get('code'),
            			items_name: rec.get('name'),
            			items_description: rec.get('description'),
                        items_unit: rec.get('unit'),
            			items_customer_code: rec.get('customer_code'),
            			items_customer_description: rec.get('customer_description'),
            			items_product_type: rec.get('product_type'),
            			items_product_series: rec.get('product_series'),
            			items_active_date: Ext.util.Format.date(new Date(), 'Y-m-d')
                    });

                    itemsStore.insert(itemsStore.getCount(), r);
                    store.removeAt(rowIndex);
            	}
            }
        }]
    }]
});

var selectWin = Ext.create('Ext.window.Window', {
	title: '物料号/内部型号',
	id: 'selectWin',
	height: 400,
	width: 1000,
	modal: true,
	constrain: true,
	maximizable: true,
	closeAction: 'hide',
	layout: 'fit',
	items: [selectGrid]
});