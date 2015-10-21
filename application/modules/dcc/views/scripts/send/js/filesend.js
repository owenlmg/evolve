Ext.require(['Ext.*']);
Ext.define('Ext.ux.CustomTrigger', {
    extend: 'Ext.form.field.Trigger',
    alias: 'widget.customtrigger',
    // override onTriggerClick
    onTriggerClick: function() {
        Ext.Msg.alert('Status', 'You clicked my trigger!');
    }
});
Ext.onReady(function() {
    Ext.QuickTips.init();
    Ext.Ajax.timeout = 180000;  
    
    var required = '<span style="color:red;font-weight:bold" data-qtip="Required">*</span>';

    Ext.define('supply', {
        extend: 'Ext.data.Model',
        idProperty: 'id',
        fields: [{name: "id"},
            {name: "code"},
            {name: "name"}
        ]
    });
    Ext.define('linkman', {
        extend: 'Ext.data.Model',
        idProperty: 'id',
        fields: [{name: "id"},
            {name: "email"},
            {name: "name"}
        ]
    });
    
    var filesStore = Ext.create('Ext.data.Store', {
        pageSize: 100,
        model: 'files',
        proxy: {
            type: 'ajax',
            reader: {
                root: 'topics',
                totalProperty: 'totalCount'
            },
            url: getRootPath() + '/public/dcc/edit/getfiles/method/edit'
        },
        autoLoad: false
    });

    var uploadStore = Ext.create('Ext.data.Store', {
        model: 'upload',
        proxy: {
            type: 'ajax',
            reader: 'json',
            url: getRootPath() + '/public/dcc/upload/getfiles/type/1'
        },
        autoLoad: false
    });

    var deptStore = Ext.create('Ext.data.TreeStore', {
        proxy: {
            type: 'ajax',
            url: getRootPath() + '/public/dcc/upload/gettree/method/dept',
            actionMethods: 'post'
        },
        sorters: [{
                property: 'leaf',
                direction: 'ASC'
            },
            {
                property: 'text',
                direction: 'ASC'
            }]
    });
    
    var supplyStore = Ext.create('Ext.data.Store', {
        pageSize: 100,
        model: 'supply',
        proxy: {
            type: 'ajax',
            reader: 'json',
            url: getRootPath() + '/public/dcc/send/getpartner/type/0'
        },
        autoLoad: false
    });
    
    var customStore = Ext.create('Ext.data.Store', {
    	pageSize: 100,
    	model: 'supply',
    	proxy: {
            type: 'ajax',
            reader: 'json',
    		url: getRootPath() + '/public/dcc/send/getpartner/type/1'
    	},
    	autoLoad: false
    });
    
    var linkmanStore = Ext.create('Ext.data.Store', {
    	pageSize: 100,
    	model: 'linkman',
    	proxy: {
    		type: 'ajax',
    		reader: 'json',
    		url: getRootPath() + '/public/dcc/send/getlinkman'
    	},
    	autoLoad: false
    });
    
    var employeeTreeStore = Ext.create('Ext.data.TreeStore', {
        proxy: {
            type: 'ajax',
            url: getRootPath() + '/public/dcc/upload/gettree',
            actionMethods: 'post'
        },
        sorters: [{
                property: 'leaf',
                direction: 'ASC'
            },
            {
                property: 'text',
                direction: 'ASC'
            }]
    });
    
	var filecodeStore = Ext.create('Ext.data.Store', {
	    model: 'code',
	    pageSize: 15,
	    proxy: {
        type: 'ajax',
        reader: 'json',
	        url: getRootPath() + '/public/product/bom/getfilecode'
	    },
	    autoLoad: false
	});

    var send = Ext.widget({
        xtype: 'form',
        layout: 'form',
        timeout:120000,
        frame: true,
        width: '98%',
        bodyPadding: '5 5 0',
        fieldDefaults: {
            msgTarget: 'side',
            labelWidth: 75
        },
        defaultType: 'textfield',
        items: [{
                xtype: 'textfield',
                hidden: true,
                id : 'innerdept_id',
                name : 'innerdept_id'
            }, {
                xtype: 'textfield',
                hidden: true,
                id : 'to_id',
                name : 'to_id'
            }, {
                xtype: 'fieldcontainer',
                layout: 'hbox',
                items: [{
                    xtype: 'combobox',
                    fieldLabel: '类别',
                    afterLabelTextTpl: required,
                    fieldWidth: 80,
                    id: 'sendtype',
                    name: 'sendtype',
                    typeAhead: true,
                    editable: false,
                    width: '50%',
                    triggerAction: 'all',
                    displayField: 'text',
                    valueField: 'val',
                    allowBlank: false,
                    store: Ext.create('Ext.data.Store', {
                        fields: ['text', 'val'],
                        data: [
                            {"text": "内发", "val": "内发"},
                            {"text": "外发", "val": "外发"}]
                    }),
                    listeners: {
                        change: function(obj, newValue, oldValue, eOpts) {
                            hideField([Ext.getCmp('out_custom'), Ext.getCmp('out_supply')
                                       , Ext.getCmp('supply_linkman'), Ext.getCmp('custom_linkman')
                                       , Ext.getCmp('inner_dept'), Ext.getCmp('out_sendtype')]);
                            
                            if (newValue == '外发') {
                            	showField([Ext.getCmp('out_sendtype')]);
                            } else {
                            	showField([Ext.getCmp('inner_dept')]);
                            }
                        }
                    }
                }, {
                    xtype: 'combobox',
                    fieldLabel: '外发类别',
                    afterLabelTextTpl: required,
                    id: 'out_sendtype',
                    name: 'out_sendtype',
                    typeAhead: true,
                    hidden: true,
                    editable: false,
                    width: '50%',
                    triggerAction: 'all',
                    displayField: 'text',
                    valueField: 'val',
                    allowBlank: false,
                    store: Ext.create('Ext.data.Store', {
                        fields: ['text', 'val'],
                        data: [
                            {"text": "供应商", "val": "供应商"},
                            {"text": "客户", "val": "客户"},
                            {"text": "其它", "val": "其它"}]
                    }),
                    listeners: {
                        change: function(obj, newValue, oldValue, eOpts) {
                        	if(!newValue) {
                        		return;
                        	}
                            hideField([Ext.getCmp('supply_linkman'), Ext.getCmp('custom_linkman')]);
                            if (newValue == '供应商') {
                            	hideField([Ext.getCmp('out_custom')]);
                            	showField([Ext.getCmp('out_supply')]);
                            } else if(newValue == '客户'){
                            	hideField([Ext.getCmp('out_supply')]);
                            	showField([Ext.getCmp('out_custom')]);
                            } else {
                            	hideField([Ext.getCmp('out_supply')]);
                            	hideField([Ext.getCmp('out_custom')]);
                            }
                        }
                    }
                }, new Ext.create("Ext.ux.comboboxtree", {
                    fieldLabel: '接收部门',
                    name: 'inner_dept',
                    id: 'inner_dept',
                    hiddenName: 'innerdept_id',
                    hidden: true,
                    editable: false,
                    labelWidth: 80,
                    width: '50%',
                    store: deptStore,
                    cascade: 'child', //级联方式:1.child子级联;2.parent,父级联,3,both全部级联
//                                 checkModel: 'single', //当json数据为不带checked的数据时只配置为single,带checked配置为double为单选,不配置为多选
                    onlyLeaf: true, //是否只选择叶子节点
                    displayField: 'text',
                    valueField: 'id',
                    rootId: '0',
                    rootText: 'DRP',
                    treeNodeParameter: ''
                })]
            }, {
                xtype: 'fieldcontainer',
                layout: 'hbox',
                items: [{
                    xtype: 'combobox',
                    fieldLabel: '供应商',
                    editable: true,
                    hidden: true,
                    width: '50%',
                    afterLabelTextTpl: required,
                    labelWidth: 80,
                    allowBlank: false,
                    id: 'out_supply',
                    name: 'out_supply',
	                displayField: 'name',
	                valueField: 'code',
                    triggerAction: 'all',
                    forceSelection: true,
                    selectOnFocus:true,
                    lazyRender: true,
	                store: supplyStore,
			        queryParam: 'search_code',
			        minChars: 2,
			        queryMode: 'remote',
	                listeners: {
                    	change:function(obj, newValue, oldValue, e) {
                    	    if(newValue) {
    	                	    supplyStore.each(function(r) {
    	                	    	if(r.get('code') == newValue) {
    	                	    		showField([Ext.getCmp('supply_linkman')]);
                                        	
    	                	    		linkmanStore.load({
    	                	                params: {
    	                	                	partner_id: r.get('id')
    	                	                }
    	                	            });
    	                	    	}
    	                	    });
                    	    }
                    	}
                	}
                }, {
                    xtype: 'combobox',
                    fieldLabel: '联系人',
                    afterLabelTextTpl: required,
                    id: 'supply_linkman',
                    name: 'supply_linkman',
                    typeAhead: true,
                    hidden: true,
                    editable: true,
			        queryMode: 'local',
                    width: '50%',
                    triggerAction: 'all',
                    displayField: 'name',
                    valueField: 'email',
	                store: linkmanStore,
                    allowBlank: false
                }]
            }, {
                xtype: 'fieldcontainer',
                layout: 'hbox',
                items: [{
                    xtype: 'combobox',
                    fieldLabel: '客户',
                    editable: true,
                    hidden: true,
                    afterLabelTextTpl: required,
                    labelWidth: 80,
                    allowBlank: false,
                    width: '50%',
                    id: 'out_custom',
                    name: 'out_custom',
	                displayField: 'name',
	                valueField: 'code',
                    triggerAction: 'all',
                    forceSelection: true,
                    selectOnFocus:true,
                    lazyRender: true,
	                store: customStore,
			        queryParam: 'search_code',
			        minChars: 2,
			        queryMode: 'remote',
	                listeners: {
                    	change:function(obj, newValue, oldValue, e) {
                    	    if(newValue) {
                    	    	customStore.each(function(r) {
    	                	    	if(r.get('code') == newValue) {
    	                	    		showField([Ext.getCmp('custom_linkman')]);
    	                	    		
    	                	    		linkmanStore.load({
    	                	                params: {
    	                	                	partner_id: r.get('id')
    	                	                }
    	                	            });
    	                	    	}
    	                	    });
                    	    }
                    	}
                	}
                }, {
                    xtype: 'combobox',
                    fieldLabel: '联系人',
                    afterLabelTextTpl: required,
                    id: 'custom_linkman',
                    name: 'custom_linkman',
                    typeAhead: true,
                    hidden: true,
                    editable: true,
			        queryMode: 'local',
                    width: '50%',
                    triggerAction: 'all',
                    displayField: 'name',
                    valueField: 'email',
	                store: linkmanStore,
                    allowBlank: false
                }]
            }, {
                xtype: 'textfield',
                fieldLabel: '自定收件人',
                fieldWidth: 80,
                id: 'personal_linkman',
                name: 'personal_linkman'
            }, {
                xtype : 'employeebobox',
                fieldLabel: '内部收件人',
                itemId: 'to',
                name: 'to',
                width: 460,
                allowBlank: true
            }, {
                xtype : 'employeebobox',
                fieldLabel: '抄送',
                itemId: 'cc',
                name: 'cc',
                width: 460,
                allowBlank: true
            }, {
            xtype: 'textfield',
            fieldLabel: '抄送',
            hidden: true,
            msgTarget: 'side',
            id: 'cc_id',
            name: 'cc_id'
        }, {
            xtype: 'textfield',
            fieldLabel: '收件人称呼',
            hidden:true,
            fieldWidth: 80,
            name: 'to_name'
        }, {
            xtype: 'textfield',
            fieldLabel: '主题',
            afterLabelTextTpl: required,
            fieldWidth: 80,
            allowBlank: false,
            id: 'subject',
            name: 'subject'
        }, {
            xtype: 'textfield',
            fieldLabel: '文件ID',
            hidden: true,
            id: 'exfile_ids',
            name: 'exfile_ids'
        }, {
            xtype: 'triggerfield',
            fieldLabel: '文件',
            afterLabelTextTpl: required,
            editable: false,
            name: 'exfile',
            id: 'exfile',
            allowBlank: false,
            triggerCls: 'x-form-search-trigger',
            onTriggerClick: function() {
                winUpload.show();
            }
        }, {
            xtype: 'htmleditor',
            fontFamilies: ['Arial', 'Courier New', 'Tahoma', 'Times New Roman', 'Verdana', '微软雅黑', 'calibri'],
            fieldLabel: '正文',
            afterLabelTextTpl: required,
            allowBlank: false,
            style: 'margin:0',
            id: 'content',
            name: 'content'
        }, {
            xtype: 'textarea',
            fieldLabel: '备注',
            rows: 2,
            name: 'remark'
        }, {
            xtype: 'htmleditor',
            fontFamilies: ['Arial', 'Courier New', 'Tahoma', 'Times New Roman', 'Verdana', '微软雅黑', 'calibri'],
            fieldLabel: '签名',
            height: 80,
            name: 'footer'
        }],
        buttons: [{
            text: '发送',
            handler: function() {
                var form = send;
                if (form.isValid() && Ext.getCmp('content').getValue()) {
                	if(Ext.getCmp('sendtype').getValue() == '内发' && 
                			!Ext.getCmp('innerdept_id').getValue() && 
                			!Ext.getCmp('to_id').getValue()) {
                		Ext.MessageBox.alert('错误', '请选择接收部门或收件人');
                		return false;
                	}
                	if(Ext.getCmp('sendtype').getValue() == '外发' && 
                			Ext.getCmp('out_sendtype').getValue() == '其它' && 
                			!Ext.getCmp('personal_linkman').getValue() && 
                			!Ext.getCmp('to').getValue()) {
                		Ext.MessageBox.alert('错误', '请至少填写一个收件人');
                		return false;
                	}
                	if(Ext.getCmp('personal_linkman').getValue()) {
                		var m = Ext.getCmp('personal_linkman').getValue();
                		var ms = m.split(',');
                		for(var i = 0; i < ms.length; i++) {
                			if(ms[i] && ms[i].indexOf('@') !== -1 && ms[i].indexOf('.') !== -1) {
                				continue;
                			} else {
                				Ext.MessageBox.alert('错误', "请正确填写自定收件人，多个收件人以“'”隔开");
                        		return false;
                			}
                		}
                	}
                    form.submit({
                        waitMsg: '发送中，请稍后...',
                        clientValidation: true,
                        submitEmptyText: false,
                        url: getRootPath() + '/public/dcc/send/send',
                        success: function(form, action) {
                            if (action.result.result) {
                                Ext.MessageBox.alert('提示', action.result.info);
                                form.reset();
                                setTimeout(function() {
                                	window.parent.closeWin(action.result.info);
                                }, 2000);
                            } else {
                                Ext.MessageBox.alert('错误', action.result.info);
                            }
                        },
                        failure: function(response) {
                            Ext.MessageBox.alert('错误', action.result.info);
                        }
                    });
                }
            }
        }, {
            text: '取消',
            handler: function() {
            	window.parent.closeWin();
            }
        }]
    });

	function hideField(field)  
    {
		if(typeof field == 'object') {
			for(var f in field) {
				if(field[f]) {
    				field[f].setValue('');
    				field[f].disable();
    				field[f].hide(); 
				}
			}
		} else {
			if(field) {
                field.setValue('');
                field.disable();// for validation  
                field.hide();  
			}
		}
    }  
  
    function showField(field)  
    {  
		if(typeof field == 'object') {
			for(var f in field) {
				if(field[f]) {
    				field[f].enable();
    				field[f].show(); 
				}
			}
		} else if(field) {
            field.enable();// for validation  
            field.show();  
		}
    }

    // 文件选择grid
    var uploadSelect = Ext.create('Ext.grid.Panel', {
        store: uploadStore,
        border:0,
        selType: 'checkboxmodel',
        columnLines: true,
        tbar: [{
                xtype: 'textfield',
                id: 'search_name2',
                emptyText: '文件号...',
                listeners: {
                    specialKey: function(field, e) {
                        if (e.getKey() == Ext.EventObject.ENTER) {
                            uploadStore.loadPage(1);
                        }
                    }
                }
            }, {
                xtype: 'textfield',
                id: 'search_description',
                emptyText: '描述...',
                listeners: {
                    specialKey: function(field, e) {
                        if (e.getKey() == Ext.EventObject.ENTER) {
                            uploadStore.loadPage(1);
                        }
                    }
                }
            }, {
                xtype: 'datefield',
                format: 'Y-m-d',
                width: 100,
                id: 'search_date_from',
                emptyText: '归档日期从...'
            }, {
                xtype: 'datefield',
                format: 'Y-m-j',
                width: 100,
                id: 'search_date_to',
                emptyText: '归档日期至...'
            }, {
                text: '查询',
                iconCls: 'icon-search',
                handler: function() {
                    var search_name = Ext.getCmp('search_name2').getValue();
                    var search_description = Ext.getCmp('search_description').getValue();
                    var search_date_from = Ext.getCmp('search_date_from').getValue();
                    var search_date_to = Ext.getCmp('search_date_to').getValue();

                    uploadStore.baseParams = {
                        search_name: search_name,
                        search_description: search_description,
                        search_archive_date_from: search_date_from,
                        search_archive_date_to: search_date_to,
                        search_del: 0,
                        search_archive: 1,
                        full: 0
                    }
                    uploadStore.loadPage(1);
                }
            }, {
                text: '<font color="blue">选择</font>',
                formBind: true,
                handler: function() {
                    var selection = uploadSelect.getView().getSelectionModel().getSelection();

                    if (selection.length > 0) {
                        var exFile = Ext.getCmp('exfile');
                        var exFileIds = Ext.getCmp('exfile_ids');
                        var name = exFile.getValue();
                        var id = exFileIds.getValue();
                        var ids = new Array();
                        if(id) {
                            ids = id.split(',');
                        }
                        for (var i = 0; i < selection.length; i++) {
                            var cont = true;
                            for(var k = 0; k < ids.length; k++) {
                                if(selection[i].get('id') == ids[k]) {
                                    cont = false;
                                    continue;
                                }
                            }
                            if(cont) {
                                if (name) {
                                    name += " | ";
                                    id += ",";
                                }
                                name += selection[i].get('name');
                                id += selection[i].get('id');
                            }
                        }
                        exFile.setValue(name);
                        exFileIds.setValue(id);

                        winUpload.hide();
                    } else {
                        Ext.MessageBox.alert('错误', '没有选择对象！');
                    }

                }
            }, {
                text: '清空选择',
                handler: function() {
                    var exFile = Ext.getCmp('exfile');
                    var exFileIds = Ext.getCmp('exfile_ids');
                    exFile.setValue('');
                    exFileIds.setValue('');
                }
            }, {
                text: '取消',
                handler: function() {
                    winUpload.hide();
                }
            }],
        columns: [{
                xtype: 'rownumberer'
            }, {
                text: 'ID',
                flex: .5,
                hidden: true,
                dataIndex: 'id'
            }, {
                text: '文件名称',
                flex: 1.5,
                sortable: true,
                dataIndex: 'name',
                renderer: showTitle
            }, {
                text: '文件类型',
                flex: 0.8,
                sortable: true,
                dataIndex: 'type'
            }, {
                text: '描述',
                flex: 1.5,
                dataIndex: 'description',
                renderer: showTitle
            }, {
                text: '备注',
                flex: 1,
                dataIndex: 'remark',
                renderer: showTitle
            }, {
                text: '上传者',
                flex: 0.7,
                hidden: true,
                dataIndex: 'updater'
            }, {
                text: '上传时间',
                flex: 1,
                hidden: true,
                dataIndex: 'upload_time',
                renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')
            }]
    });

    uploadStore.on("beforeload", function() {
        var search_name = Ext.getCmp('search_name2').getValue();
        var search_description = Ext.getCmp('search_description').getValue();
        var search_date_from = Ext.getCmp('search_date_from').getValue();
        var search_date_to = Ext.getCmp('search_date_to').getValue();

        Ext.apply(uploadStore.proxy.extraParams, {
            search_name: search_name,
            search_description: search_description,
            search_archive_date_from: search_date_from,
            search_archive_date_to: search_date_to,
            search_del: 0,
            search_archive: 1,
            full: 0
        });
    });
    
    var winUpload = new Ext.Window({
        xtype: "window",
        border:0,
        title: '文件选择',
        height: 400,
        width: 800,
        modal: true,
        layout: 'fit',
        closeAction: 'hide',
        items: [uploadSelect],
        tools: [{
                type: 'refresh',
                tooltip: '刷新表格数据',
                scope: this,
                handler: function() {
                    uploadStore.reload();
                }
            }]
    });
    
    send.render(document.body);
});