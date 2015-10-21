// 项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id", type: 'int'},
             {name: "items_price_id"},
             {name: "items_type"},
             {name: "items_code"},
             {name: "items_name"},
             {name: "items_unit"},
             {name: "items_active_date",type: 'date',dateFormat: 'Y-m-d'},
             {name: "items_description"},
             {name: "items_customer_code"},
             {name: "items_customer_description"},
             {name: "items_remark"},
             {name: "items_ladder"},
             {name: "items_product_type"},
             {name: "items_product_series"},
             {name: "items_price_start", type: "float"},
             {name: "items_price_final", type: "float"}]
});

Ext.define('Ladder', {
    extend: 'Ext.data.Model',
    fields: [{name: "item_id"},
             {name: "id"},
             {name: "qty"},
             {name: "price_start", type: 'float'},
             {name: "price_final", type: 'float'},
             {name: "remark"}]
});

var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/sale_price/getitems'
    }
});

function setLadderJson(store){
	var selection = itemsGrid.getView().getSelectionModel().getSelection();
	
	if(selection.length > 0){
		var selected = selection[0];
		var ladder = [];
	    
	    store.each(function(rec){
	 	   ladder.push(rec.data);
	    });
	    
	    selected.set('items_ladder', Ext.JSON.encode(ladder));
	}
}

var ladderStore = Ext.create('Ext.data.Store', {
    model: 'Ladder',
    listeners: {
    	datachanged: function(store){
    		setLadderJson(store);
    	},
    	update: function(store){
    		setLadderJson(store);
    	}
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
        beforeedit: function(editor, e){
            if(Ext.getCmp('priceSaveBtn').isDisabled()){
                return false;
            }
        }
    }
});

var ladderRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
        beforeedit: function(editor, e){
            if(Ext.getCmp('priceSaveBtn').isDisabled()){
                return false;
            }
        }
    }
});

function getCurrentCurrency(){
    return Ext.getCmp('price_form').getForm().findField("currency").getValue();
}

//阶梯价
var ladderGrid = Ext.create('Ext.grid.Panel', {
    id: 'ladderGrid',
    minHeight: 320,
    maxHeight: 320,
    margin: '0 0 0 2',
    flex: 1,
    columnLines: true,
    multiSelect: true,
    selModel: {
       mode: 'MULTI'
    },
    store: ladderStore,
    tbar: [{
       text: '添加',
       disabled: true,
       id: 'ladderAddBtn',
       iconCls: 'icon-add',
       handler: function(){
    	   var selection = itemsGrid.getView().getSelectionModel().getSelection();
    	   var rec = selection[0];
    	   var data = {
	   			item_id: rec.get('items_id'),
	   			qty: 0,
	   			price_start: 0,
	   			price_final: 0
           };
    	   var r = Ext.create('Ladder', data);

           ladderStore.insert(ladderStore.getCount(), r);
       }
    }, {
       text: '删除',
       disabled: true,
       id: 'ladderDeleteBtn',
       iconCls: 'icon-delete',
       handler: function(){
           var selection = ladderGrid.getView().getSelectionModel().getSelection();
    
           if(selection.length == 0){
               Ext.MessageBox.alert('错误', '没有选择删除对象！');
           }else{
               ladderStore.remove(selection);
           }
       }
    }, '->', {
    	xtype: 'displayfield',
    	value: '阶梯价'
    }],
    plugins: ladderRowEditing,
    columns: [{
       xtype: 'rownumberer'
    }, {
       text: 'ID',
       align: 'center',
       hidden: true,
       dataIndex: 'id',
       width: 50
    }, {
       text: '数量',
       dataIndex: 'qty',
       align: 'center',
       editor: {
    	   xtype: 'numberfield',
    	   selectOnFocus: true,
    	   minValue: 0
       },
       renderer: function(val, meta, record){
           meta.style = 'background-color: #DFFFDF';
           
           return val;
       },
       flex: 1
    }, {
       text: '初始价格',
       hidden: true,
       editor: {
    	   xtype: 'numberfield',
    	   selectOnFocus: true,
    	   decimalPrecision: 4,
    	   minValue: 0
       },
       renderer: function(value, meta, rec){
           return setMoney(value, getCurrentCurrency());
       },
       dataIndex: 'price_start',
       flex: 1
    }, {
       text: '价格',
       editor: {
    	   xtype: 'numberfield',
    	   selectOnFocus: true,
    	   decimalPrecision: 4,
    	   minValue: 0
       },
       renderer: function(value, meta, rec){
           return setMoney(value, getCurrentCurrency());
       },
       dataIndex: 'price_final',
       flex: 1
    }, {
       text: '备注',
       hidden: true,
       dataIndex: 'remark',
       editor: 'textfield',
       flex: 1
    }]
});

// 项目列表
var itemsGrid = Ext.create('Ext.grid.Panel', {
    minHeight: 320,
    maxHeight: 320,
    flex: 2,
    id: 'itemsGrid',
    columnLines: true,
    multiSelect: true,
    selModel: {
        mode: 'MULTI'
    },
    store: itemsStore,
    tbar: [{
        text: '添加',
        id: 'itemsAddBtn',
        iconCls: 'icon-add',
        handler: function(){
            selectWin.show();
        }
    }, {
        text: '删除',
        id: 'itemsDeleteBtn',
        iconCls: 'icon-delete',
        handler: function(){
            var selection = itemsGrid.getView().getSelectionModel().getSelection();

            if(selection.length == 0){
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }else{
                itemsStore.remove(selection);
            }
        }
    }, '->', {
        text: '审核日志',
        handler: function(){
            viewReviewInfo(priceForm.getForm().findField('review_info').getValue());
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
        text: '类别',
        align: 'center',
        dataIndex: 'items_type',
        renderer: function(val, meta){
            return val == 'catalog' ? '内部型号' : '物料代码';
        },
        width: 80
    }, {
        text: '产品型号',
        dataIndex: 'items_code',
        width: 160
    }, {
        text: '产品类别',
        dataIndex: 'items_product_type',
        width: 100
    }, {
        text: '产品系列',
        dataIndex: 'items_product_series',
        width: 100
    }, {
        text: '初始价格',
        hidden: true,
        editor: new Ext.form.NumberField({  
            decimalPrecision: 4,
            minValue: 0
        }),
        renderer: function(value, meta, rec){
            return setMoney(value, getCurrentCurrency());
        },
        dataIndex: 'items_price_start',
        width: 100
    }, {
        text: '价格',
        editor: new Ext.form.NumberField({  
            decimalPrecision: 4,
            minValue: 0
        }),
        renderer: function(value, meta, rec){
            return setMoney(value, getCurrentCurrency());
        },
        dataIndex: 'items_price_final',
        width: 100
    }, {
        text: '生效日期',
        align: 'center',
        dataIndex: 'items_active_date',
        renderer: Ext.util.Format.dateRenderer('Y-m-d'),
        editor: {
            xtype: 'datefield',
            editable: false,
            format: 'Y-m-d'
        },
        width: 110
    }, {
        text: '单位',
        align: 'center',
        editor: 'textfield',
        dataIndex: 'items_unit',
        width: 60
    }, {
        text: '名称',
        dataIndex: 'items_name',
        width: 120
    }, {
        text: '描述',
        dataIndex: 'items_description',
        width: 180
    }, {
        text: '客户产品型号',
        dataIndex: 'items_customer_code',
        width: 100
    }, {
        text: '客户产品描述',
        dataIndex: 'items_customer_description',
        width: 100
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 200
    }],
    listeners: {
    	selectionchange: function( sel, selected, eOpts ){
            if(selected.length == 1){
            	if(!Ext.getCmp('priceSaveBtn').isDisabled()){
            		Ext.getCmp('ladderAddBtn').enable();
                	Ext.getCmp('ladderDeleteBtn').enable();
            	}else{
            		Ext.getCmp('ladderAddBtn').disable();
                	Ext.getCmp('ladderDeleteBtn').disable();
            	}
            	
            	var ladder = selected[0].get('items_ladder');
            	
            	if(ladder != '' && ladder != undefined){
            		ladderStore.loadData(Ext.JSON.decode(ladder));
            	}else{
            		ladderStore.removeAll();
            	}
            }else{
            	Ext.getCmp('ladderAddBtn').disable();
            	Ext.getCmp('ladderDeleteBtn').disable();
            }
        }
    }
});

var priceForm = Ext.create('Ext.form.Panel', {
    id: 'price_form',
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
            labelWidth: 70,
            labelAlign: 'right'
        },
        items: [{
            xtype: 'hiddenfield',
            name: 'state'
        }, {
            xtype: 'hiddenfield',
            name: 'review_info'
        }, {
            xtype: 'hiddenfield',
            name: 'operate'
        }, {
            xtype: 'hiddenfield',
            name: 'id'
        }, {
            xtype: 'hiddenfield',
            name: 'current_step'
        }, {
            xtype: 'hiddenfield',
            name: 'last_step'
        }, {
            xtype: 'hiddenfield',
            name: 'to_finish'
        }, {
            xtype: 'hiddenfield',
            name: 'next_step'
        }, {
            name: 'number',
            xtype:'displayfield',
            fieldLabel: '编号',
            labelWidth: 60,
            flex: 1
        }, {
            name: 'price_date',
            xtype: 'datefield',
            format: 'Y-m-d',
            value: Ext.util.Format.date(new Date(), 'Y-m-d'),
            width: 170,
            editable: false,
            fieldLabel: '申请日期'
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
                    var index = customerStore.findExact('id', newValue);
                    
                    if(index != -1){
                        var currency = customerStore.getAt(index).data.currency;
                        
                        sel.up('form').getForm().findField("currency").setValue(currency);
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
        msgTarget : 'side',
        layout: 'hbox',
        items: [itemsGrid, ladderGrid]
    }],
    buttons: [{
        text: '审核',
        id: 'priceReviewBtn',
        handler: function(){
            reviewPrice();
        }
    }, {
        text: '提交',
        id: 'priceSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            if(form.isValid()){
                var updateRecords = itemsStore.getUpdatedRecords();
                var insertRecords = itemsStore.getNewRecords();
                var deleteRecords = itemsStore.getRemovedRecords();
                
                // 检查数据有效性
                if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                    var errInfo = '';
                    
                    var items = {
                            inserted: [],
                            updated: [],
                            deleted: []
                    };
                    
                    for(var i = 0; i < insertRecords.length; i++){
                        var rec = insertRecords[i];
                        
                        if(rec.get('code') == ''){
                            errInfo = '请输入物料号/内部型号';
                            
                            break;
                        }
                        
                        items.inserted.push(rec.data);
                    }
                    
                    if(errInfo == ''){
                        for(var i = 0; i < updateRecords.length; i++){
                            var rec = updateRecords[i];
                            
                            if(rec.get('code') == ''){
                                errInfo = '请输入物料号/内部型号';
                                
                                break;
                            }
                            
                            items.updated.push(rec.data);
                        }
                    }
                    
                    if(errInfo == ''){
                        for(var i = 0; i < deleteRecords.length; i++){
                            var rec = deleteRecords[i];
                            
                            items.updated.push(rec.data);
                        }
                    }
                    
                    if(errInfo == ''){
                        var data = {
                                id       		: form.findField('id').getValue(),
                                state        	: form.findField('state').getValue(),
                                operate        	: form.findField('operate').getValue(),
                                current_step	: form.findField('current_step').getValue(),
                                last_step    	: form.findField('last_step').getValue(),
                                to_finish    	: form.findField('to_finish').getValue(),
                                next_step    	: form.findField('next_step').getValue(),
                                number        	: form.findField('number').getValue(),
                                price_date    	: form.findField('price_date').getValue(),
                                customer_id    	: form.findField('customer_id').getValue(),
                                currency    	: form.findField('currency').getValue(),
                                price_tax    	: form.findField('price_tax').getValue(),
                                description    	: form.findField('description').getValue(),
                                remark        	: form.findField('remark').getValue(),
                                items        	: items
                        };
                        
                        Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                            if(button == 'yes'){
                                Ext.Msg.wait('请稍后...', '处理中');
                                
                                Ext.Ajax.request({
                                    url: homePath+'/public/erp/sale_price/edit',
                                    params: {data: Ext.JSON.encode(data)},
                                    method: 'POST',
                                    success: function(response, options) {
                                        data = Ext.JSON.decode(response.responseText);
                                        
                                        if(data.success){
                                            form.reset();
                                            priceWin.hide();
                                            priceStore.loadPage(1);
                                            Ext.getCmp('priceGrid').getSelectionModel().clearSelections();
                                        }
                                        
                                        Ext.MessageBox.alert('提示', data.info);
                                        
                                        Ext.Msg.hide();
                                    },
                                    failure: function(response){
                                        Ext.MessageBox.alert('错误', response.statusText);
                                    }
                                });
                            }
                        });
                    }else{
                        Ext.MessageBox.alert('错误', errInfo);
                    }
                }else{
                    Ext.MessageBox.alert('错误', '数据错误，表体未检测到更新！');
                }
            }
        }
    }, {
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            priceWin.hide();
        }
    }]
});

var priceWin = Ext.create('Ext.window.Window', {
    title: '价格申请',
    id: 'priceWin',
    width: 1000,
    modal: true,
    constrain: true,
    layout: 'fit',
    maximizable: true,
    resizable: true,
    closeAction: 'hide',
    items: [priceForm]
});