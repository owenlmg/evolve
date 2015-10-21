/**
 * 通讯录
 */
Ext.define('Contact', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: "id"},
             {name: "parentId"},
             {name: "dept_id"},
             {name: "user_id"},
             {name: "employee_id"},
             {name: "text"},
             {name: "post"},
             {name: "dept"},
             {name: "cname"},
             {name: "ename"},
             {name: "email"},
             {name: "number"},
             {name: "tel"},
             {name: "official_qq"},
             {name: "work_place"},
             {name: "short_num"},
             {name: "msn"},
             {name: "ext"},
             {name: "iconCls"},
             {name: "leaf"},
             {name: "state"}]
});

var contactStore = Ext.create('Ext.data.TreeStore', {
    model: 'Contact',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath + '/public/hra/contact/getcontact/option/list'
    },
    lazyFill: true
});

var contactPanel = Ext.create('Ext.tree.Panel', {
	xtype: 'check-tree',
    rootVisible: false,
    layout: 'fit',
    store: contactStore,
    border: 0,
    listeners: {
    	checkchange: function(node, checked) {
    	    node.cascadeBy(function(n){
    	    	if(checked){
    	    		n.expand();
    	    	}else{
    	    		n.collapse();
    	    	}
    	    	n.set('checked', checked);
	    	});
    	}
    }
});

//必填提示
var required = '<span style="color:red;font-weight:bold" data-qtip="Required">*</span>';

// 消息表单
var msgForm = Ext.create('Ext.form.Panel', {
    xtype: 'form',
    id: 'msgForm',
    layout: {
        type: 'vbox',
        align: 'stretch'
    },
    border: false,
    bodyPadding: 5,

    url: homePath + '/public/hra/contact/sendmsg',
    defaultType: 'textfield',

    fieldDefaults: {
        labelAlign: 'right',
        labelWidth: 60,
        labelStyle: 'font-weight:bold'
    },

    items: [{
        xtype: 'fieldcontainer',
        layout: 'hbox',

        items: [{
        	flex: 5,
            xtype: 'textfield',
            fieldLabel: '标题',
            name: 'msg_title',
            allowBlank: false,
            afterLabelTextTpl: required,
            allowBlank: false
        }, {
        	flex: 1.2,
        	xtype:'combobox',
            displayField: 'text',
            valueField: 'val',
            triggerAction: 'all',
            value: '中',
            lazyRender: true,
            store: Ext.create('Ext.data.Store', {
                fields: ['text', 'val'],
                data: [
                    {"text": "低", "val": "低"},
                    {"text": "中", "val": "中"},
                    {"text": "高", "val": "高"},
                    {"text": "紧急", "val": "紧急"},
                    {"text": "特急", "val": "特急"}
                ]
            }),
            name: 'msg_priority',
            fieldLabel: '优先级',
            afterLabelTextTpl: required,
            editable: false,
            allowBlank: false
        }, {
            flex: 0.6,
            labelWidth: 50,
            fieldLabel: '邮件',
            name: 'msg_sendmail',
            xtype: 'checkbox'
        }]
    }, {
    	xtype: 'displayfield',
        fieldLabel: '接收人',
        id: 'msg_receivers_info',
        name: 'msg_receivers_info'
    }, {
    	fieldLabel: '内容',
    	name: 'msg_content',
    	xtype: 'htmleditor',
    	height: 200,
        anchor: '100%'
    }, {
    	fieldLabel: '备注',
    	name: 'msg_remark',
    	xtype: 'textfield',
        anchor: '100%'
    }, {
        xtype: 'hiddenfield',
        name: 'msg_receivers',
        id: 'msg_receivers'
    }, {
        xtype: 'hiddenfield',
        name: 'msg_receivers_ids',
        id: 'msg_receivers_ids'
    }, {
        xtype: 'hiddenfield',
        name: 'msg_receivers_email',
        id: 'msg_receivers_email'
    }],

    buttons: [{
        text: '提交',
        handler: function() {
            var form = this.up('form').getForm();

            if (form.isValid()) {
                form.submit({
                	waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                       Ext.Msg.alert('Success', action.result.info);
                       newMsgWin.hide();
                    },
                    failure: function(form, action) {
                        Ext.Msg.alert('Failed', action.result.info);
                    }
                });
            }
        }
    },{
        text: '取消',
        handler: function() {
            Ext.getCmp('newMsgWin').hide();
        }
    }]
});

// 消息编辑窗口
var newMsgWin = Ext.create('Ext.window.Window', {
    title: '发送消息',
    id: 'newMsgWin',
    width: 800,
    modal: true,
    constrain: true,
    resizable: false,
    layout: 'fit',
    items: msgForm,
    closeAction: 'hide'
});

// 查找联系人
function searchContact(key){
	contactStore.load({
     params: {
         key: key
     }
 });
}

//发送消息
function sendMsg(sel){
	var ids = '';
	var emails = '';
	var receivers = '';
	var receivers_cnt = 0;

	for(var i = 0; i < sel.length; i++){
		// 当用户ID和用户邮箱不为空时，记录接收人信息
		if(sel[i].data['user_id'] && sel[i].data['email'] != ''){
			if(receivers_cnt == 0){
				ids = sel[i].data['user_id'];
				emails = sel[i].data['email'];
				receivers = sel[i].data['cname'];
			}else{
				ids += ',' + sel[i].data['user_id'];
				emails += ',' + sel[i].data['email'];
				receivers += ',' + sel[i].data['cname'];
			}

			receivers_cnt++;
		}
	}

	if(ids.length > 0){
		Ext.getCmp('msg_receivers_ids').setValue(ids);
		Ext.getCmp('msg_receivers_email').setValue(emails);
		Ext.getCmp('msg_receivers_info').setValue(receivers);
		Ext.getCmp('msg_receivers').setValue(receivers);

		newMsgWin.show();
	}else{
		Ext.MessageBox.alert('提示', '没有选择用户！（只能发消息给开通系统账号的用户）');
	}
}

// 通讯录列表
var contactTreePanel = Ext.create('Ext.tree.Panel', {
    rootVisible: false,
    border: 0,
    layout: 'fit',
    store: contactStore,
    animate: false,
    xtype: 'cell-editing',
    plugins: [
		Ext.create('Ext.grid.plugin.CellEditing', {
			clicksToEdit: 2
		})
	],
	listeners: {
    	checkchange: function(node, checked) {
    	    node.cascadeBy(function(n){
    	    	if(checked){
    	    		n.expand();
    	    	}else{
    	    		n.collapse();
    	    	}
    	    	n.set('checked', checked);
	    	});
    	}
    },
    tbar: [{
    	xtype: 'textfield',
    	id: 'contactListSearchKey',
    	width: 200,
    	emptyText: '关键字...',
    	listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	var key = Ext.getCmp('contactListSearchKey').getValue();
                	searchContact(key);
                }
            }
        }
    }, {
        text: '查找',
        iconCls: 'icon-search',
        handler: function(){
        	var key = Ext.getCmp('contactListSearchKey').getValue();
        	searchContact(key);
        }
    }, {
    	text: '发送消息',
        iconCls: 'icon-user-msg',
        handler: function(){
        	var sel = contactTreePanel.getChecked();
        	sendMsg(sel);
        }
    }, '->', {
        xtype: 'hiddenfield',
        id: 'isExpandGrid',
        value: 0
    }, {
    	iconCls: 'icon-view-expand',
    	id: 'expand_or_collapse_grid',
    	text: '展开',
        scope: this,
        handler: function(){
            var btn = Ext.getCmp('expand_or_collapse_grid');
            var isExpand = Ext.getCmp('isExpandGrid').getValue();

            if(isExpand == 0){
            	Ext.getCmp('isExpandGrid').setValue(1)

            	contactTreePanel.expandAll();

            	btn.setIconCls('icon-view-collapse');
            	btn.setText('折叠');
            }else{
            	Ext.getCmp('isExpandGrid').setValue(0)

            	contactTreePanel.collapseAll();

            	btn.setIconCls('icon-view-expand');
            	btn.setText('展开');
            }
        }
    }, {
        iconCls: 'icon-refresh',
        text: '刷新',
        scope: this,
        handler: function(){contactStore.reload();}
    }],
    columns: [{
        text: '员工ID',
        hidden: true,
        dataIndex: 'employee_id',
        flex: 0.5
    }, {
        xtype: 'treecolumn',
        text: '名称',
        flex: 2,
        sortable: true,
        dataIndex: 'text'
    }, {
        text: '职位',
        align: 'center',
        flex: 1,
        dataIndex: 'post'
    }, {
        text: '中文名',
        align: 'center',
        flex: 1,
        dataIndex: 'cname'
    }, {
        text: '英文名',
        align: 'center',
        flex: 1,
        dataIndex: 'ename'
    }, {
        text: '工作地点',
        align: 'center',
        flex: 1,
        dataIndex: 'work_place'
    }, {
        text: '邮箱',
        flex: 2,
        dataIndex: 'email'
    }, {
        text: '电话',
        align: 'center',
        flex: 1,
        dataIndex: 'tel'
    }, {
        text: '企业QQ',
        hidden: true,
        align: 'center',
        flex: 1,
        dataIndex: 'official_qq'
    }, {
        text: '短号',
        hidden: true,
        align: 'center',
        flex: 1,
        dataIndex: 'short_num'
    }, {
        text: 'MSN',
        hidden: true,
        align: 'center',
        flex: 1,
        dataIndex: 'msn'
    }, {
        text: '分机号',
        align: 'center',
        flex: 1,
        dataIndex: 'ext'
    }]
});

// 通讯录窗口
var contactWin = Ext.create('Ext.window.Window', {
	title: '通讯录',
	maximizable: true,
	height: 500,
	width: 1000,
	modal: true,
	constrain: true,
	closeAction: 'hide',
	layout: 'fit',
	items: [contactTreePanel]
});

//通讯录
var addressbookArea = Ext.create('Ext.tab.Panel', {
    region: 'east',
    title: '通讯录',
    width: 260,
    animCollapse: true,
    collapsible: true,
    collapsed: true,
    split: true,
    tabPosition: 'bottom',
    defaults :{
        autoScroll: true
    },
    tools: [{
    	type: 'maximize',
    	tooltip: '打开通讯录列表',
    	callback: function (panel, tool) {
    		contactWin.show();
    	}
    }],
    items: [{
        title: '通讯录',
    	items: [{
            region: 'north',
            border: 0,
            dockedItems: [{
                dock: 'top',
                xtype: 'buttongroup',
                margins: '0 0 0 0',
                items: [{
                	xtype: 'textfield',
                	id: 'contactSearchKey',
                	width: 100,
                	emptyText: '关键字...',
                	listeners: {
                    	specialKey :function(field,e){
                            if (e.getKey() == Ext.EventObject.ENTER){
                            	var key = Ext.getCmp('contactSearchKey').getValue();
                            	searchContact(key);
                            }
                        }
                    }
                }, {
                    text: '查找',
                    iconCls: 'icon-search',
                    handler: function(){
                    	var key = Ext.getCmp('contactSearchKey').getValue();
                    	searchContact(key);
                    }
                }, {
                	tooltip: '发送消息',
                    iconCls: 'icon-user-msg',
                    handler: function(){
                    	var sel = contactPanel.getChecked();
                    	sendMsg(sel);
                    }
                }, {
                    xtype: 'hiddenfield',
                    id: 'isExpand',
                    value: 0
                }, {
                	iconCls: 'icon-view-expand',
                	id: 'expand_or_collapse',
                	tooltip: '展开/折叠',
                    handler: function(){
                        var btn = Ext.getCmp('expand_or_collapse');
                        var isExpand = Ext.getCmp('isExpand').getValue();

                        if(isExpand == 0){
                        	Ext.getCmp('isExpand').setValue(1)

                        	contactPanel.expandAll();

                        	btn.setIconCls('icon-view-collapse');
                        }else{
                        	Ext.getCmp('isExpand').setValue(0)

                        	contactPanel.collapseAll();

                        	btn.setIconCls('icon-view-expand');
                        }
                    }
                }]
            }]
        }, {
            region: 'center',
            border: 0,
            layout: 'fit',
            plain: true,
            items: [contactPanel]
        }]
    }, {
        title: '聊天记录',
        autoScroll: true,
        html: '<p>聊天记录</p>'
    }]
});