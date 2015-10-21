// 新建业务伙伴窗口
var partnerWin = Ext.create('Ext.window.Window', {
    title: '新建业务伙伴',
    border: 0,
    width: 800,
    modal: true,
    constrain: true,
    layout: 'fit',
    closeAction: 'hide',
    items: [{
        region: 'center',
        split: true,
        items: [{
            xtype: 'form',
            border: 0,
            id: 'partner_form',
            url: homePath+'/public/erp/bpartner/editpartner',
            bodyPadding: '5 5 0',
            fieldDefaults: {
                msgTarget: 'side',
                labelWidth: 50
            },
            items: [{
                xtype: 'container',
                layout:'hbox',
                items:[{
                    xtype: 'hiddenfield',
                    name: 'edit_type',
                    id: 'edit_type',
                    value: 'new'
                }, {
                    xtype: 'hiddenfield',
                    name: 'partner_id',
                    id: 'partner_id',
                    value: 0
                }, {
                    xtype: 'container',
                    flex: 1,
                    border:false,
                    layout: 'anchor',
                    defaultType: 'textfield',
                    items: [{
                        fieldLabel: '代码',
                        afterLabelTextTpl: required,
                        allowBlank: false,
                        name: 'code',
                        anchor:'95%'
                    }, {
                    	xtype:'combobox',
                        typeAhead: true,
                        editable: false,
                        triggerAction: 'all',
                        displayField: 'text',
                        valueField: 'val',
                        store: Ext.create('Ext.data.Store', {
                            fields: ['text', 'val'],
                            data: [
                                {"text": "是", "val": 1},
                                {"text": "否", "val": 0}
                            ]
                        }),
                        fieldLabel: '启用',
                        value: 1,
                        name: 'active',
                        afterLabelTextTpl: required,
                        allowBlank: false,
                        anchor:'95%'
                    }, {
                        fieldLabel: 'RSM',
                        name: 'rsm',
                        anchor:'95%'
                    }]
                }, {
                    xtype: 'container',
                    flex: 2,
                    border:false,
                    layout: 'anchor',
                    defaultType: 'textfield',
                    items: [{
                        fieldLabel: '中文名称',
                        labelWidth: 75,
                        name: 'cname',
                        anchor:'95%'
                    }, {
                        fieldLabel: '英文名称',
                        labelWidth: 75,
                        name: 'ename',
                        anchor:'95%'
                    }, {
                        fieldLabel: '终端客户',
                        labelWidth: 75,
                        name: 'terminal_customer',
                        anchor:'95%'
                    }]
                }, {
                    xtype: 'container',
                    flex: 1.5,
                    layout: 'anchor',
                    defaultType: 'textfield',
                    items: [{
                    	xtype:'combobox',
                        typeAhead: true,
                        editable: false,
                        triggerAction: 'all',
                        displayField: 'name',
                        valueField: 'id',
                        store: partnerTypeStore,
                        fieldLabel: '类别',
                        name: 'type',
                        afterLabelTextTpl: required,
                        allowBlank: false,
                        listeners: {
                            change: function(combo, newValue, oldValue, eOpts){
                                if(newValue != oldValue){
                                	var type = 0;

                                    if(newValue == '客户' || newValue == 1){
                                    	type = 1;
                                    }
                                    
                                	Ext.getCmp('partner_form').getForm().findField('group_id').clearValue();
                                    
                                	groupListStore.load({
                                        params: {
                                        	type: type
                                        }
                                    });
                                }
                            }
                        },
                        anchor:'100%'
                    },{
                    	xtype:'combobox',
                        displayField: 'name',
                        valueField: 'id',
                        triggerAction: 'all',
                        lazyRender: true,
                        store: groupListStore,
                        queryMode: 'local',
                        name: 'group_id',
                        fieldLabel: '组',
                        afterLabelTextTpl: required,
                        editable: false,
                        allowBlank: false,
                        anchor:'100%'
                    }, {
                        fieldLabel: '后缀',
                        name: 'suffix',
                        anchor:'100%'
                    }]
                }]
            },{
                xtype:'tabpanel',
                border: 0,
                margin: '2 0 0 0',
                plain: true,
                tabBar: {
                	height: 24,
                	defaults: {
                		height: 22
                    }
                },
                activeTab: 0,
                items:[{
                    title:'联系方式',
                    region: 'center',
                    layout: 'fit',
                    border: 0,
                    items: [contactGrid]
                }, {
                    title:'付款信息',
                    height: 210,
                    layout: 'form',
                    border: 0,
                    defaults: {
                        xtype: 'textfield',
                    	labelStyle: 'font-weight:bold',
                    	labelWidth: 70,
                    	labelAlign: 'right'
                    },
                    items: [{
                        xtype: 'fieldcontainer',
                        msgTarget : 'side',
                        layout: 'hbox',
                        defaults: {
                            xtype: 'textfield',
                        	labelStyle: 'font-weight:bold',
                        	labelWidth: 70,
                        	labelAlign: 'right'
                        },
                        items: [{
                        	name: 'bank_currency', 
                        	xtype:'combobox',
                        	displayField: 'name',
                        	valueField: 'id',
                        	triggerAction: 'all',
                        	value: 1,
                        	lazyRender: true,
                        	queryMode: 'local',
                        	afterLabelTextTpl: required,
                        	editable: false,
                        	store: currencyStore,
                        	fieldLabel: '币种',
                            flex: 1
                        }, {
                        	name: 'tax_id', 
                        	xtype:'combobox',
                        	displayField: 'name',
                        	valueField: 'id',
                        	triggerAction: 'all',
                        	value: 1,
                        	lazyRender: true,
                        	queryMode: 'local',
                        	afterLabelTextTpl: required,
                        	editable: false,
                        	store: taxStore,
                        	fieldLabel: '税率',
                            flex: 1
                        }, {
                        	name: 'bank_payment_days', 
                        	xtype:'combobox',
                        	displayField: 'name',
                        	valueField: 'id',
                        	triggerAction: 'all',
                        	value: 3,
                        	lazyRender: true,
                        	queryMode: 'local',
                        	afterLabelTextTpl: required,
                        	editable: false,
                        	store: paymentListStore,
                        	fieldLabel: '付款方式',
                            flex: 1
                        }]
                    }, {
                        fieldLabel: '银行国家',
                        name: 'bank_country',
                        anchor:'100%'
                    }, {
                        fieldLabel: '开户行',
                        name: 'bank_type',
                        anchor:'100%'
                    }, {
                        fieldLabel: '账号',
                        name: 'bank_account',
                        anchor:'100%'
                    }, {
                        fieldLabel: '税号',
                        name: 'tax_num',
                        anchor:'100%'
                    }, {
                        fieldLabel: '开户名称',
                        name: 'bank_name',
                        anchor:'100%'
                    }, {
                        fieldLabel: '备注',
                        name: 'bank_remark',
                        anchor:'100%'
                    }]
                }, {
                    cls: 'x-plain',
                    title: '备注',
                    layout: 'fit',
                    border: 0,
                    items: {
                        xtype: 'htmleditor',
                        height: 200,
                        name: 'remark'
                    }
                }]
            }]
        }]
    }],
    buttons: [{
        text: '提交',
        id: 'partnerSubBtn',
        disabled: bpartnerAdminDisabled,
        handler: function() {
            var form = Ext.getCmp('partner_form').getForm();

            if(form.findField('cname').getValue() == '' && form.findField('ename').getValue() == ''){
                Ext.MessageBox.alert('错误', '中文名称、英文名称不能全部为空！');
            }else{
                if(form.isValid()){
                	// 当业务伙伴数据保存成功后，保存其联系人及联系地址列表信息
                	var contactUpdateRecords = contactStore.getUpdatedRecords();
                    var contactInsertRecords = contactStore.getNewRecords();
                    var contactDeleteRecords = contactStore.getRemovedRecords();

                    var changeRowCnt = contactUpdateRecords.length + contactInsertRecords.length + contactDeleteRecords.length;

                    if(false){//Ext.getCmp('edit_type').getValue() == 'edit' && changeRowCnt == 0 && !isFormItemsChange(form)
                        // 当编辑类别为修改时，判断是否有修改内容
                    	Ext.MessageBox.alert('错误', '没有修改任何数据！');
                    }else{
                    	Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                            if(button == 'yes'){
                                form.submit({
                                    waitMsg: '提交中，请稍后...',
                                    success: function(form, action) {
                                        var data = action.result;

                                        if(data.success){
                                            var errInfo = '';
                                            
                                            if(data.partner_id){
                                                // 当业务伙伴数据保存成功后，保存其联系人及联系地址列表信息
                                            	var contactUpdateRecords = contactStore.getUpdatedRecords();
                                                var contactInsertRecords = contactStore.getNewRecords();
                                                var contactDeleteRecords = contactStore.getRemovedRecords();

                                                // 当检查到数据有被修改时
                                                if(changeRowCnt > 0){
                                                	var changeRows = {
                                                			partner_id: data.partner_id,
                                                            contact: {updated: [], inserted: [], deleted: []}
                                                    }

                                                	// 记录联系人更新数据
                                                	for(var i = 0; i < contactUpdateRecords.length; i++){
                                                        changeRows.contact.updated.push(contactUpdateRecords[i].data)
                                                    }
                                                	// 记录联系人插入数据
                                                    for(var i = 0; i < contactInsertRecords.length; i++){
                                                        changeRows.contact.inserted.push(contactInsertRecords[i].data)
                                                    }
                                                    // 记录联系人删除数据
                                                    for(var i = 0; i < contactDeleteRecords.length; i++){
                                                        changeRows.contact.deleted.push(contactDeleteRecords[i].data)
                                                    }

                                                    var json = Ext.JSON.encode(changeRows);

                                                    Ext.Ajax.request({
                                                        url: homePath+'/public/erp/bpartner/editlistinfo',
                                                        params: {json: json},
                                                        method: 'POST',
                                                        success: function(response, options) {
                                                            var data = Ext.JSON.decode(response.responseText);

                                                            // 当保存联系人或地址出错，记录错误信息
                                                            if(!data.success){
                                                            	errInfo = data.info;
                                                            }
                                                        },
                                                        failure: function(response){
                                                        	errInfo = '保存提交失败';
                                                        }
                                                    });
                                                }
                                            }

                                            // 当保存联系人或地址出错，提示错误信息
                                            if(errInfo != ''){
                                            	Ext.MessageBox.alert('错误', errInfo);
                                            }else{
                                            	Ext.MessageBox.alert('提示', '保存成功');
                                                form.reset();
                                                partnerWin.hide();
                                                partnerStore.loadPage(1);
                                                Ext.getCmp('partnerGrid').getSelectionModel().clearSelections();
                                            }
                                        }else{
                                            Ext.MessageBox.alert('错误', data.info);
                                        }
                                    },
                                    failure: function(response, result){
                                        Ext.MessageBox.alert('错误', result.result.info);
                                    }
                                });
                            }
                        });
                    }
                }
            }
        }
    },{
        text: '取消',
        handler: function() {
            Ext.getCmp('partner_form').getForm().reset();
            partnerWin.hide();
        }
    }]
});