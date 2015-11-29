// 请购项目
Ext.define('Items', {
    extend: 'Ext.data.Model',
    fields: [{name: "items_id"},
             {name: "items_transfer_type"},
             {name: "items_active"},
             {name: "items_req_id"},
             {name: "items_code"},
             {name: "items_name"},
             {name: "items_mpq"},
             {name: "items_moq"},
             {name: "items_description"},
             {name: "items_qty"},
             {name: "items_qty_order"},
             {name: "items_unit"},
             {name: "items_project_info"},
             {name: "items_model"},
             {name: "items_price"},
             {name: "items_is_changing"},
             {name: "items_line_total"},
             {name: "items_date_req",type: 'date',dateFormat: 'Y-m-d'},
             {name: "items_supplier"},
             {name: "items_dept_id", type: "int"},
             {name: "items_remark"},
             {name: "items_order_req_num"},
             {name: "items_customer_address"},
             {name: "items_customer_aggrement"}]
});

//角色设置数据源
var itemsStore = Ext.create('Ext.data.Store', {
    model: 'Items',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/purchse_req/getreqitems'
    }
});

var itemsRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1,
    listeners: {
    	beforeedit: function(editor, e){
    		var record = e.record;
    		
    		if(Ext.getCmp('reqSaveBtn').isDisabled() 
			|| (!Ext.getCmp('transfer').isHidden() && Ext.getCmp('transfer_type').getValue() == '取消')){
    			return false;
    		}else if((e.field == 'items_name' || e.field == 'items_description') && record.get('items_code') != ""){
    			e.cancel = true;
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

function printReq(){
	var number = reqForm.getForm().findField('number').getValue();
	var type_id = reqForm.getForm().findField('type_id').getValue();
	var id = reqForm.getForm().findField('id').getValue();
	
	if(id != null){
		Ext.Msg.wait('处理中，请稍后...', '提示');
	    Ext.Ajax.request({
	        url: homePath + '/public/erp/purchse_req/getprint/id/' + id + '/type_id/' +　type_id,
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
        url: homePath+'/public/erp/purchse_req/importitems',
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
                        			items_active: true,
                        			items_code: data[i].code,
                        			items_is_changing: data[i].is_changing,
                        			items_name: data[i].name,
                        			items_description: data[i].description,
                        			items_unit: data[i].unit,
                        			items_qty: data[i].qty,
                        			items_qty_order: 0,
                        			items_date_req: Ext.Date.clearTime(new Date(data[i].date_req)),
                        			items_project_info: data[i].project_info,
                        			items_order_req_num: data[i].order_req_num,
                        			items_customer_address: data[i].customer_address,
                        			items_customer_aggrement: data[i].customer_aggrement,
                                    items_remark: data[i].remark
                                });

                    			itemsStore.insert(itemsStore.getCount(), r);
                                itemsGrid.getSelectionModel().select(itemsStore.getCount() - 1);
                    		}
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(form, action){
                        Ext.MessageBox.alert('错误', action.result.info);
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
            if(record.get('active') == 0){
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
                items_price: 0,
                items_qty: 0,
                items_qty_order: 0,
                items_line_total: 0,
                items_dept_id: 1/*,
                items_date_req: Ext.Date.clearTime(new Date())*/
            });

            itemsStore.insert(itemsStore.getCount(), r);
            itemsGrid.getSelectionModel().select(itemsStore.getCount() - 1);
            //itemsRowEditing.startEdit(itemsStore.getCount() - 1);
        }
    }, {
        text: '删除',
        id: 'itemsDeleteBtn',
        iconCls: 'icon-delete',
        handler: function(){
            var selection = itemsGrid.getView().getSelectionModel().getSelection();

            if(selection.length > 0){
            	if(Ext.getCmp('operate').getValue() == 'transfer' && selection[0].get('items_qty_order') > 0){
            		Ext.MessageBox.alert('错误', '当前记录已下单，不能删除！');
            	}else{
            		itemsStore.remove(selection);
            	}
            }else{
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }
        }
    }, {
    	text: '导入',
    	id: 'itemsImportBtn',
    	iconCls: 'icon-csv',
    	tooltip: '根据CSV文件导入物料采购清单',
    	handler: function(){
    		importWin.show();
    	}
    }, {
    	text: '导入模板',
    	iconCls: 'icon-csv',
    	tooltip: '导入物料采购清单必须参照模板格式',
    	handler: function(){
    		window.open(homePath+'/library/导入_采购申请.csv');
    	}
    }, '->', {
    	text: '变更内容',
    	handler: function(){
    		var id = reqForm.getForm().findField('transfer_id').getValue();
    		var type = reqForm.getForm().findField('transfer_type').getValue();
    		var description = reqForm.getForm().findField('transfer_description').getValue();
    		
    		if(id != ''){
    			viewTransferContent(id, type, description);
    		}
    	}
    }, {
    	text: '变更日志',
    	handler: function(){
    		viewTransferInfo(reqForm.getForm().findField('id').getValue());
    	}
    }, {
    	text: '审核日志',
    	handler: function(){
    		viewReviewInfo(reqForm.getForm().findField('review_info').getValue());
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
        		
        		if(!checked && rec.get('items_qty_order') > 0){
        			Ext.MessageBox.show({
                        title: '错误',
                        msg: '当前记录已下单，不能取消！',
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
            		var selection = Ext.getCmp('itemsGrid').getView().getSelectionModel().getSelection();
            		
            		if(selection.length > 0){
            			var rec = selection[0];
                		var index = codeStore.findExact('text',newValue);

                		var name = '';
                		var description = '';
                		var mpq = 1;
                		var moq = 1;
                		var unit = '个';
                		
                        if (index != -1){
                        	rs = codeStore.getAt(index).data;

                        	name = rs.name;
                        	mpq = rs.mpq;
                        	moq = rs.moq;
                        	
                        	if(rs.unit != undefined){
                        		unit = rs.unit;
                        	}

                        	description = rs.description;
                        }

                        rec.set('items_name', name);
                        rec.set('items_mpq', mpq);
                        rec.set('items_moq', moq);
                        rec.set('items_unit', unit);
                    	rec.set('items_description', description);
                    	
                    	Ext.Msg.wait('处理中，请稍后...', '提示');
                    	
                    	Ext.Ajax.request({
                            url: homePath+'/public/erp/purchse_req/checkdesc',
                            params: {code: newValue},
                            method: 'POST',
                            success: function(response, options) {
                            	result = Ext.JSON.decode(response.responseText);
                            	
                            	Ext.Msg.hide();
                            	
                            	if(result.success){
                            		rec.set('items_is_changing', true);
                            	}else{
                            		rec.set('items_is_changing', false);
                            	}
                            },
                            failure: function(response){
                            	Ext.MessageBox.alert('错误', newValue + '物料变更状态获取失败');
                            }
                        });
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
        align: 'center',
        editor: 'numberfield',
        dataIndex: 'items_qty',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	
        	return val;
        },
        width: 80
    }, {
        text: '已下单',
        align: 'center',
        dataIndex: 'items_qty_order',
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
        text: '需求日期',
        align: 'center',
        dataIndex: 'items_date_req',
        renderer: Ext.util.Format.dateRenderer('Y-m-d'),
        editor: {
            xtype: 'datefield',
            editable: false,
            format: 'Y-m-d'
        },
        width: 110
    }, {
        text: '单价',
        editor: new Ext.form.NumberField({  
            decimalPrecision: 4,
            minValue: 0
        }),
        renderer: moneyRenderer,
        dataIndex: 'items_price',
        summaryRenderer: function(value, summaryData, dataIndex) {
            return '合计：';
        },
        width: 100
    }, {
        text: '金额',
        dataIndex: 'items_line_total',
        renderer: function(value, metaData, record, rowIdx, colIdx, store, view) {
            return moneyRenderer(record.get('items_qty') * record.get('items_price'));
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
        summaryRenderer: moneyRenderer,
        width: 120
    }, {
        text: '供应商',
        align: 'center',
        editor: 'textfield',
        dataIndex: 'items_supplier',
        width: 100
    }, {
        text: '型号',
        align: 'center',
        editor: 'textfield',
        dataIndex: 'items_model',
        width: 100
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
        align: 'center',
        editor: 'textfield',
        dataIndex: 'items_project_info',
        width: 200
    }, {
        text: '订货产品出库申请号',
        dataIndex: 'items_order_req_num',
        editor: 'textfield',
        width: 150
    }, {
        text: '客户收件人地址简码',
        dataIndex: 'items_customer_address',
        editor: 'textfield',
        width: 150
    }, {
        text: '客户合同号',
        dataIndex: 'items_customer_aggrement',
        editor: 'textfield',
        width: 150
    }, {
        text: '备注',
        dataIndex: 'items_remark',
        editor: 'textfield',
        width: 200
    }]
});

var reqForm = Ext.create('Ext.form.Panel', {
	id: 'req_form',
	border: 0,
    url: homePath+'/public/erp/purchse_req/editreq',
    bodyPadding: '5 5 0',
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
           labelWidth: 50,
           labelAlign: 'right'
       },
       items: [{
           xtype: 'hiddenfield',
           name: 'transfer_id',
           id: 'req_transfer_id'
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
           labelWidth: 70,
           fieldLabel: '编号',
           flex: 1.5
       }, {
           name: 'hand_number',
           id: 'hand_number',
           hidden: true,
           labelWidth: 70,
           xtype: 'textfield',
           fieldLabel: '编号',
           flex: 1.5
       }, {
           name: 'hand',
           id: 'hand',
           hidden: true,
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
           name: 'dept_id', 
           xtype:'combobox',
           displayField: 'name',
           valueField: 'id',
           triggerAction: 'all',
           value: 1,
           lazyRender: true,
           queryMode: 'local',
           afterLabelTextTpl: required,
           editable: false,
           store: deptStore,
           fieldLabel: '申请部门',
           labelWidth: 70,
           flex: 1.5
       }, {
           name: 'apply_user', 
           xtype:'combobox',
           displayField: 'name',
           valueField: 'id',
           triggerAction: 'all',
           value: user_id,
           lazyRender: true,
           queryMode: 'local',
           afterLabelTextTpl: required,
           editable: false,
           allowBlank: false,
           store: employeeListStore,
           fieldLabel: '申请人',
           labelWidth: 70,
           flex: 1.5
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
           allowBlank: false,
           store: typeListStore,
           fieldLabel: '采购类别',
           listeners: {
        	   change: function( sel, newValue, oldValue, eOpts ){
        		   if(this.up('form').getForm().findField('operate').getValue() != ''){
        			   var index = typeListStore.findExact('id', newValue);
        			   if(index != -1){
        				   var type = typeListStore.getAt(index).data.name;
        				   Ext.getCmp('itemsImportBtn').disable();
        				   
        				   if(type == '研发物料' || type == '量产物料' || type == '物料原材料' || type == '辅料工具' || type == '外协加工'){
        					   // 隐藏列
        					   itemsGrid.columns[3].show();
        					   itemsGrid.columns[15].show();
        					   itemsGrid.columns[16].show();
        					   itemsGrid.columns[17].show();
        					   itemsGrid.columns[18].show();
        					   
        					   Ext.getCmp('itemsImportBtn').enable();
        				   }else{
        					   itemsGrid.columns[3].hide();
        					   itemsGrid.columns[15].hide();
        					   itemsGrid.columns[16].hide();
        					   itemsGrid.columns[17].hide();
        					   itemsGrid.columns[18].hide();
        				   }
        				   
        				   if(type != '办公用品'){
        					   itemsGrid.columns[14].show();
        				   }else{
        					   itemsGrid.columns[14].hide();
        				   }
        			   }
        		   }
               }
           },
           labelWidth: 70,
           flex: 1.5
       }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
            xtype:'textfield',
            name: 'reason', 
            fieldLabel: '事由',
            labelStyle: 'font-weight:bold',
            labelWidth: 70,
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
           labelWidth: 70,
           labelAlign: 'right',
           flex: 1
       }]
    }, {
        xtype: 'fieldcontainer',
        hidden: true,
        id: 'transfer',
        msgTarget : 'side',
        layout: 'hbox',
        items: [{
        	name: 'transfer_type',
        	id: 'transfer_type',
        	xtype:'combobox',
        	displayField: 'text',
        	valueField: 'val',
        	triggerAction: 'all',
        	lazyRender: true,
        	queryMode: 'local',
        	editable: false,
        	value: '修改',
        	store: Ext.create('Ext.data.Store', {
                fields: ['text', 'val'],
                data: [
                    {"text": "修改", "val": "修改"},
                    {"text": "取消", "val": "取消"}
                ]
            }),
        	fieldLabel: '变更类别',
            labelAlign: 'right',
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
        	listeners: {
        		change: function( sel, newValue, oldValue, eOpts ){
        			if(newValue == '修改'){
        				Ext.getCmp('itemsAddBtn').enable();
        				Ext.getCmp('itemsDeleteBtn').enable();
        				Ext.getCmp('itemsImportBtn').enable();
        			}else{
        				Ext.getCmp('itemsAddBtn').disable();
        				Ext.getCmp('itemsDeleteBtn').disable();
        				Ext.getCmp('itemsImportBtn').disable();
        			}
        		}
        	},
        	width: 140
        }, {
            xtype:'textfield',
            id: 'transfer_description',
            name: 'transfer_description',
            fieldLabel: '变更说明',
            labelStyle: 'font-weight:bold',
            labelWidth: 70,
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
    		printReq();
    	}
    }, {
    	text: '审核',
    	id: 'reqReviewBtn',
    	handler: function(){
    		reviewReq();
    	}
    }, {
        text: '提交',
        id: 'reqSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            var hand_number = Ext.getCmp('hand_number').getValue();
            var hand = Ext.getCmp('hand').getValue();
            
            if(Ext.getCmp('operate').getValue() == 'transfer' && Ext.getCmp('transfer_description').getValue() == ''){
            	Ext.MessageBox.alert('错误', '请填写变更说明！');
            }else if(hand && hand_number == ''){
                Ext.MessageBox.alert('错误', '请填写申请单编号！');
            }else if(form.isValid()){
                // 检查数据有效性
            	if(itemsStore.getCount() > 0){
            		var itemsUpdateRecords = itemsStore.getUpdatedRecords();
                    var itemsInsertRecords = itemsStore.getNewRecords();
                    var itemsDeleteRecords = itemsStore.getRemovedRecords();
                    
                    var valChk = true;
                    var errInfo = '';
                    var alertInfo = '';
                    
                    for(var i = 0; i < itemsUpdateRecords.length; i++){
                    	var rec = itemsUpdateRecords[i];
                    	
                    	var code = rec.get('items_code');
                    	var name = rec.get('items_name');
                    	var description = rec.get('items_description');
                    	var is_changing = rec.get('items_is_changing');
                    	var qty = rec.get('items_qty');
                    	var qty_order = rec.get('items_qty_order');
                    	var mpq = rec.get('items_mpq');
                    	var moq = rec.get('items_moq');
                    	var date_req = rec.get('items_date_req');
                    	
                		if(Ext.getCmp('operate').getValue() != 'transfer' && date_req < Ext.Date.clearTime(new Date())){
                    		valChk = false;
                    		errInfo = code + '需求日期错误!';
                    		
                    		break;
                    	}else if(code != '' && name == '' && description == ''){
                    		valChk = false;
                    		errInfo = code + '物料号错误!';
                    		
                    		break;
                    	}
                    	
                    	if(qty > 0){
                    		if(qty < qty_order){
                    		    var modifiedJson = Ext.JSON.encode(rec.modified);
                    		    
                    		    if(modifiedJson.indexOf('items_qty') != -1){
                    		        valChk = false;
                                    errInfo = code + '下单数量[' + qty + ']小于已下单数量[' + qty_order + ']';
                                    
                                    break;
                    		    }
                        		
                        	}
                    		
                    		if(mpq > 0 && qty % mpq != 0){
                    			alertInfo += '<br>' + code + '下单数量[' + qty + ']不满足最小包装量[' + mpq + ']';
                        	}else if(moq > 0 && qty < moq){
                        		alertInfo += '<br>' + code + '下单数量[' + qty + ']不满足最小订单量[' + moq + ']';
                        	}
                    	}else{
                    		valChk = false;
                    		errInfo = code + '数量为0，请填写下单数量!';
                    		
                    		break;
                    	}
                    	
                    	if(is_changing){
                    		alertInfo += '<br>' + code + '正在进行物料变更';
                    	}
                    }
                    
                    if(valChk){
                    	for(var i = 0; i < itemsInsertRecords.length; i++){
                        	var rec = itemsInsertRecords[i];
                        	
                        	var code = rec.get('items_code');
                        	var name = rec.get('items_name');
                        	var description = rec.get('items_description');
                        	var is_changing = rec.get('items_is_changing');
                        	var qty = rec.get('items_qty');
                        	var qty_order = rec.get('items_qty_order');
                        	var mpq = rec.get('items_mpq');
                        	var moq = rec.get('items_moq');
                        	var date_req = rec.get('items_date_req');
                        	
                        	if(date_req < Ext.Date.clearTime(new Date())){
                        		valChk = false;
                        		errInfo = code + '需求日期错误!';
                        		
                        		break;
                        	}else if(code != '' && name == '' && description == ''){
                        		valChk = false;
                        		errInfo = code + '物料号错误!';
                        		
                        		break;
                        	}
                        	
                        	if(qty > 0){
                        		if(qty < qty_order){
                            		valChk = false;
                            		errInfo = code + '下单数量[' + qty + ']小于已下单数量[' + qty_order + ']';
                            		
                            		break;
                            	}
                        		
                        		if(mpq > 0 && qty % mpq != 0){
                        			alertInfo += '<br>' + code + '下单数量[' + qty + ']不满足最小包装量[' + mpq + ']';
                            	}else if(moq > 0 && qty < moq){
                            		alertInfo += '<br>' + code + '下单数量[' + qty + ']不满足最小订单量[' + moq + ']';
                            	}
                        	}else{
                        		valChk = false;
                        		errInfo = code + '数量为0，请填写下单数量!';
                        		
                        		break;
                        	}
                        	
                        	if(is_changing){
                        		alertInfo += '<br>' + code + '正在进行物料变更';
                        	}
                        }
                    }
                    
                    if(valChk){
                    	var changeRowCnt = itemsUpdateRecords.length + itemsInsertRecords.length + itemsDeleteRecords.length;

                        Ext.MessageBox.confirm('确认', '确定提交？' + alertInfo, function(button, text){
                            if(button == 'yes'){
                                form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    success: function(form, action) {
                                        var data = action.result;

                                        if(data.success){
                                            var errInfo = '';
                                            
                                            if(data.req_id){
                                                // 当检查到数据有被修改时
                                                var changeRows = {
                                                	req_id: data.req_id,
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
                                                    url: homePath+'/public/erp/purchse_req/edititems',
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
                                            }

                                            // 当保存采购项目出错，提示错误信息
                                            if(errInfo != ''){
                                                Ext.MessageBox.alert('错误', errInfo);
                                            }else{
                                                Ext.MessageBox.alert('提示', '保存成功');
                                                form.reset();
                                                reqWin.hide();
                                                reqStore.loadPage(1);
                                                Ext.getCmp('reqGrid').getSelectionModel().clearSelections();
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
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            reqWin.hide();
        }
    }]
});

// 请购单窗口
var reqWin = Ext.create('Ext.window.Window', {
	title: '采购申请',
	id: 'reqWin',
	width: 1000,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [reqForm],
	tools: [{
		type:'help'
	}]
});