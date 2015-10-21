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
             {name: "items_warehouse_code"}]
});

// 数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/stock_in/getinstockitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		if(Ext.getCmp('instockSaveBtn').isDisabled()){
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
	        url: homePath + '/public/erp/stock_in/getprint/id/' + id,
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

function setPrice(code, rec){
	Ext.Ajax.request({
        url: homePath+'/public/erp/stock_in/getprice',
        params: {code: code},
        method: 'POST',
        success: function(response, options) {
        	result = Ext.JSON.decode(response.responseText);
        	
        	// 获取价格
        	rec.set('items_price', result.price);
        },
        failure: function(response){
        	Ext.MessageBox.alert('错误', code + '价格获取提交失败');
        }
    });
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
        url: homePath+'/public/erp/stock_in/importitems/type/in',
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
                        			items_warehouse_code: data[i].warehouse_code,
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
                items_name: '',
                items_description: '',
                items_price: 0
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
                    		var unit = '个';
                    		var description = '';
                    		
                            if (index != -1){
                            	rs = codeStore.getAt(index).data;

                            	name = rs.name;
                            	unit = rs.unit;
                            	description = rs.description;

                            	setPrice(newValue, rec);
                            }

                            // 选择物料号，自动填充名称、描述
                            rec.set('items_name', name);
                            rec.set('items_unit', unit);
                        	rec.set('items_description', description);
                        }
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
        text: '入库数量',
        dataIndex: 'items_qty',
        align: 'center',
        editor: 'numberfield',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	
        	return val;
        },
        renderer: inputAreaRender,
        width: 80
    }, {
        text: '单位',
        align: 'center',
        dataIndex: 'items_unit',
        width: 60
    }, /*{
        text: '入库价格',
        align: 'center',
        dataIndex: 'items_price',
        editor: 'numberfield',
        renderer: function(val, cellmeta){
        	cellmeta.tdAttr = 'data-qtip="系统入库价格：[库存均价] [价格清单最低价]"';
        	
        	return Ext.util.Format.currency(val, '￥', 2);
        },
        width: 80
    }, */{
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
    url: homePath+'/public/erp/stock_in/editinstock',
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
        	width: 220
        }, {
        	name: 'date',
        	xtype: 'datefield',
        	format: 'Y-m-d',
        	value: Ext.util.Format.date(new Date(), 'Y-m-d'),
        	width: 170,
        	editable: false,
        	fieldLabel: '收货日期'
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
        id: 'instockSaveBtn',
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
                    	var warehouse_code = rec.get('items_warehouse_code');
                    	var qty = rec.get('items_qty');

                    	if(code == ''){
                    		valChk = false;
                    		errInfo = '物料号不能为空！';
                    		
                    		break;
                    	}else if(qty == 0){
                    		valChk = false;
                    		errInfo = code + '数量为0，请填写收货数量!';
                    		
                    		break;
                    	}else if(warehouse_code == 0){
                    		valChk = false;
                    		errInfo = code + '未填写收货仓库!';
                    		
                    		break;
                    	}
                    }
                    
                    if(valChk){
                    	for(var i = 0; i < itemsInsertRecords.length; i++){
                        	var rec = itemsInsertRecords[i];
                        	
                        	var code = rec.get('items_code');
                        	var description = rec.get('items_description');
                        	var warehouse_code = rec.get('items_warehouse_code');
                        	var qty = rec.get('items_qty');

                        	if(code == ''){
                        		valChk = false;
                        		errInfo = '物料号不能为空！';
                        		
                        		break;
                        	}else if(code != '' && description == ''){
                        		valChk = false;
                        		errInfo = code + '物料描述为空，请检查料号设置是否正确！';
                        		
                        		break;
                        	}else if(qty == 0){
                        		valChk = false;
                        		errInfo = code + '数量为0，请填写收货数量!';
                        		
                        		break;
                        	}else if(warehouse_code == 0){
                        		valChk = false;
                        		errInfo = code + '未填写收货仓库!';
                        		
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
                                            
                                            if(data.instock_id){
                                                // 当检查到数据有被修改时
                                                //if(changeRowCnt > 0){
                                                    var changeRows = {
                                                		instock_id: data.instock_id,
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
                                                        url: homePath+'/public/erp/stock_in/edititems',
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
                                                            errInfo = '收货项目保存提交失败';
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
                                                instockWin.hide();
                                                instockStore.loadPage(1);
                                                Ext.getCmp('instockGrid').getSelectionModel().clearSelections();
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
            		Ext.MessageBox.alert('错误', '请添加收货项目！');
            	}
            }
        }
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            instockWin.hide();
        }
    }]
});

// 收货单窗口
var instockWin = Ext.create('Ext.window.Window', {
	title: '库存交易-收货',
	id: 'instockWin',
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