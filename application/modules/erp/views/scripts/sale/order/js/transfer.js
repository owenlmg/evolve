function viewTransferContent(id, transfer_type, transfer_description){
	transferContentWin.show();
	
	Ext.getCmp('transfer_transfer_type').setValue(transfer_type);
	Ext.getCmp('transfer_transfer_description').setValue(transfer_description);
	
    transferItemsStore.load({
        params: {
            transfer_id: id
        }
    });
}

var transferItemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_order/getordertransferitems'
    }
});

var transferItemsGrid = Ext.create('Ext.grid.Panel', {
	minHeight: 420,
    maxHeight: 420,
    id: 'transferItemsGrid',
    columnLines: true,
    selModel: {
        mode: 'MULTI'
    },
    store: transferItemsStore,
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
        	var transfer_type = record.get('items_transfer_type');
        	
            if(transfer_type == 'update'){
                return 'view-update-row';
            }else if(transfer_type == 'delete'){
                return 'view-delete-row';
            }
        }
    },
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: 'ID',
        align: 'center',
        hidden: true,
        dataIndex: 'items_id',
        width: 50
    }, {
        text: '变更类别',
        dataIndex: 'items_transfer_type',
        align: 'center',
        renderer: function(val){
        	if(val == 'update'){
        		return '更新';
        	}else if(val == 'add'){
        		return '添加';
        	}else if(val == 'delete'){
        		return '删除';
        	}
        },
        width: 80
    }, {
        text: '启用',
        dataIndex: 'items_active',
        align: 'center',
        renderer: activeRender,
        width: 50
    }, {
        text: '内部型号 / 物料号',
        dataIndex: 'items_code',
        width: 120
    }, {
        text: '名称',
        dataIndex: 'items_name',
        width: 120
    }, {
        text: '描述',
        dataIndex: 'items_description',
        width: 180
    }, {
        text: '数量',
        dataIndex: 'items_qty',
        align: 'center',
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
        dataIndex: 'items_unit',
        width: 60
    }, {
        text: '单价',
        renderer: function(value, meta, rec){
        	return setMoney(value, getCurrentCurrency());
        },
        dataIndex: 'items_price',
        width: 100
    }, {
        text: '金额',
        dataIndex: 'items_total',
        width: 120
    }, {
        text: '需求日期',//多个申请时，取最早日期
        align: 'center',
        dataIndex: 'items_request_date',
        renderer: Ext.util.Format.dateRenderer('Y-m-d'),
        width: 110
    }, {
        text: '申请单号',
        dataIndex: 'items_req_number',
        width: 120
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        width: 200
    }]
});

var transferContentForm = Ext.create('Ext.form.Panel', {
	id: 'transferContentForm',
	border: 0,
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
           labelWidth: 75,
           labelAlign: 'right'
       },
       items: [{
    	   fieldLabel: '变更类别',
           xtype: 'displayfield',
           id: 'transfer_transfer_type',
           name: 'transfer_type'
       }, {
    	   fieldLabel: '变更说明',
           xtype: 'displayfield',
           id: 'transfer_transfer_description',
           name: 'transfer_description'
       }]
    }, transferItemsGrid],
    buttons: [{
    	text: '审核',
    	id: 'transferReviewBtn',
    	handler: function(){
    		reviewOrder();
    	}
    }, {
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            transferContentWin.hide();
        }
    }]
});

var transferContentWin = Ext.create('Ext.window.Window', {
    title: '变更内容',
    border: 0,
    width: 1000,
    modal: true,
    constrainHeader: true,
    id: 'transferContentWin',
    layout: 'fit',
    maximizable: true,
    resizable: true,
    closeAction: 'hide',
    resizable: true,
    items: [transferContentForm]
});