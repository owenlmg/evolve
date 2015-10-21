var contactForm = Ext.create('Ext.form.Panel', {
	id: 'contactForm',
	border: 0,
	url: homePath+'/public/erp/bpartner_customercontact/editcontact',
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
        	labelAlign: 'right',
        	flex: 1.2
        },
        items: [{
        	xtype: 'hiddenfield',
        	name: 'operate',
        }, {
        	xtype: 'hiddenfield',
        	name: 'id'
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
        	selectOnFocus: true,
        	autoSelect: true,
        	allowBlank: false,
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
                }
            },
        	flex: 5
        }, {
        	xtype: 'checkboxfield',
        	fieldLabel: '启用',
        	checked: true,
        	name: 'active'
        }, {
        	xtype: 'checkboxfield',
        	fieldLabel: '默认',
        	checked: false,
        	name: 'default'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
            xtype:'textfield',
        	flex: 1,
        	labelAlign: 'right'
        },
        items: [{
            name: 'name', 
            fieldLabel: '联系人'
        }, {
            name: 'post', 
            fieldLabel: '职位'
        }, {
            name: 'tel', 
            fieldLabel: '电话'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
            xtype:'textfield',
        	flex: 1,
        	labelAlign: 'right'
        },
        items: [{
            name: 'email', 
            fieldLabel: '邮箱',
            flex: 2
        }, {
            name: 'fax', 
            fieldLabel: '传真',
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
            xtype:'textfield',
        	flex: 1,
        	labelAlign: 'right'
        },
        items: [{
            name: 'country', 
            fieldLabel: '国家'
        }, {
            name: 'area', 
            fieldLabel: '省/州/县'
        }, {
            name: 'area_city', 
            fieldLabel: '城市'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
            xtype:'textfield',
        	flex: 1,
        	labelAlign: 'right'
        },
        items: [{
            name: 'area_code', 
            allowBlank: false,
            fieldLabel: '地址简码',
            flex: 2
        }, {
            name: 'zip_code', 
            fieldLabel: '邮编'
        }]
    }, {
        xtype: 'fieldcontainer',
        msgTarget : 'side',
        layout: 'hbox',
        defaults: {
        	labelStyle: 'font-weight:bold',
        	labelWidth: 70,
            xtype:'textfield',
        	flex: 1,
        	labelAlign: 'right'
        },
        items: [{
            name: 'address', 
            fieldLabel: '详细地址',
            flex: 2
        }, {
            name: 'person_liable', 
            fieldLabel: '责任人',
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
    }],
    buttons: [{
        text: '提交',
        id: 'contactSaveBtn',
        handler: function() {
            var form = this.up('form').getForm();
            
            if(form.isValid()){
            	form.submit({
                    waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                        var data = action.result;

                        if(data.success){
                            contactWin.hide();
                            contactStore.loadPage(1);
                            Ext.getCmp('contactGrid').getSelectionModel().clearSelections();
                            Ext.getCmp('contactCopyBtn').disable();
                    		Ext.getCmp('contactEditBtn').disable();
                    		Ext.getCmp('contactDeleteBtn').disable();
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(response){
                        Ext.MessageBox.alert('错误', '保存提交失败');
                    }
                });
            }
        }
    },{
        text: '取消',
        handler: function() {
            this.up('form').getForm().reset();
            contactWin.hide();
        }
    }]
});

// 维护窗口
var contactWin = Ext.create('Ext.window.Window', {
	title: '客户联系人',
	id: 'contactWin',
	width: 600,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [contactForm]
});