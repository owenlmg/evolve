// 渲染联系方式按钮
var partnerContactRender = function(val){
    return '<img style="cursor:pointer;" onclick="openContact(\'contact\', ' + val + ');" src="'+homePath+'/public/images/icons/contact.png"></img>';
};

// 联系人数据模型
Ext.define('Contact', {
    extend: 'Ext.data.Model',
    fields: [{name: "contact_id"}, 
             {name: "contact_active"}, 
             {name: "contact_default"}, 
             {name: "contact_partener_id"}, 
             {name: "contact_name"}, 
             {name: "contact_post"}, 
             {name: "contact_tel"}, 
             {name: "contact_fax"}, 
             {name: "contact_email"}, 
             {name: "contact_country"}, 
             {name: "contact_area"}, 
             {name: "contact_area_city"}, 
             {name: "contact_area_code"}, 
             {name: "contact_person_liable"}, 
             {name: "contact_address"}, 
             {name: "contact_zip_code"}, 
             {name: "contact_remark"}]
});

// 联系人数据源
var contactStore = Ext.create('Ext.data.Store', {
    model: 'Contact',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/erp/bpartner/getcontact'
    }
});

// 联系人编辑插件
var contactRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

// 联系人查看列表
var contactInfoGrid = Ext.create('Ext.grid.Panel', {
    height: 200,
    border: 0,
    columnLines: true,
    store: contactStore,
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(record.get('contact_active') == 0){
                // 离职员工背景色为灰色
                return 'gray-row';
            }
        }
    },
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: '联系人',
        dataIndex: 'contact_name',
        width: 120
    }, {
        text: '职位',
        dataIndex: 'contact_post',
        width: 120
    }, {
        text: '电话',
        dataIndex: 'contact_tel',
        width: 160
    }, {
        text: '传真',
        dataIndex: 'contact_fax',
        width: 160
    }, {
        text: '邮箱',
        dataIndex: 'contact_email',
        width: 180
    }, {
        text: '责任人',
        dataIndex: 'contact_person_liable',
        width: 120
    }, {
        text: '地址简码',
        dataIndex: 'contact_area_code',
        width: 120
    }, {
        text: '国家',
        dataIndex: 'contact_country',
        width: 120
    }, {
        text: '省/州/县',
        dataIndex: 'contact_area',
        width: 120
    }, {
        text: '城市',
        dataIndex: 'contact_area_city',
        width: 120
    }, {
        text: '地址名称',
        dataIndex: 'contact_address',
        width: 280
    }, {
        text: '邮编',
        dataIndex: 'contact_zip_code',
        width: 120
    }, {
        text: '备注',
        dataIndex: 'contact_remark',
        width: 300
    }]
});

// 联系人编辑列表
var contactGrid = Ext.create('Ext.grid.Panel', {
    height: 200,
    border: 0,
    columnLines: true,
    store: contactStore,
    selType: 'checkboxmodel',
    tbar: [{
        text: '添加',
        disabled: bpartnerAdminDisabled,
        iconCls: 'icon-user-add',
        handler: function(){
        	contactRowEditing.cancelEdit();
            
            var r = Ext.create('Contact', {
            	contact_active: true
            });

            contactStore.insert(0, r);
            contactRowEditing.startEdit(0, 0);
        }
    }, {
        text: '删除',
        disabled: bpartnerAdminDisabled,
        iconCls: 'icon-user-delete',
        handler: function(){
            var selection = contactGrid.getView().getSelectionModel().getSelection();

            if(selection.length > 0){
            	contactStore.remove(selection);
            }else{
                Ext.MessageBox.alert('错误', '没有选择删除对象！');
            }
        }
    }, {
    	text: '导入',
        disabled: bpartnerAdminDisabled,
    	id: 'itemsImportBtn',
    	iconCls: 'icon-csv',
    	tooltip: '根据CSV文件导入联系人',
    	handler: function(){
    		importWin.show();
    	}
    }, '->', {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
            contactStore.reload();
        }
    }],
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(record.get('contact_active') == 0){
                // 离职员工背景色为灰色
                return 'gray-row';
            }
        }
    },
    plugins: contactRowEditing,
    columns: [{
        xtype: 'rownumberer'
    }, {
        xtype: 'checkcolumn',
        text: '启用',
        dataIndex: 'contact_active',
        width: 60
    }, {
        xtype: 'checkcolumn',
        text: '默认',
        dataIndex: 'contact_default',
        stopSelection: false,
        listeners: {
            checkchange: function (column, recordIndex, checked) {
                if(checked){
                	for(var i = 0, len = contactStore.data.length; i < len; i++){
                		if(i != recordIndex){
                			var data = contactStore.getAt(i).set('contact_default', false);
                		}
                	}
                }
            }
        },
        width: 60
    }, {
        text: '联系人',
        dataIndex: 'contact_name',
        editor: 'textfield',
        width: 120
    }, {
        text: '职位',
        dataIndex: 'contact_post',
        editor: 'textfield',
        width: 120
    }, {
        text: '电话',
        dataIndex: 'contact_tel',
        editor: 'textfield',
        width: 160
    }, {
        text: '传真',
        dataIndex: 'contact_fax',
        editor: 'textfield',
        width: 160
    }, {
        text: '邮箱',
        dataIndex: 'contact_email',
        editor: 'textfield',
        width: 180
    }, {
        text: '责任人',
        dataIndex: 'contact_person_liable',
        editor: 'textfield',
        width: 120
    }, {
        text: '地址简码',
        dataIndex: 'contact_area_code',
        editor: 'textfield',
        width: 120
    }, {
        text: '国家',
        dataIndex: 'contact_country',
        editor: 'textfield',
        width: 80
    }, {
        text: '省/州/县',
        dataIndex: 'contact_area',
        editor: 'textfield',
        width: 80
    }, {
        text: '城市',
        dataIndex: 'contact_area_city',
        editor: 'textfield',
        width: 80
    }, {
        text: '地址名称',
        dataIndex: 'contact_address',
        editor: 'textfield',
        width: 200
    }, {
        text: '邮编',
        dataIndex: 'contact_zip_code',
        editor: 'textfield',
        width: 100
    }, {
        text: '备注',
        dataIndex: 'contact_remark',
        editor: 'textfield',
        width: 300
    }]
});

// 联系方式窗口
var contactWin = Ext.create('Ext.window.Window', {
    title: '联系方式',
    border: 0,
    width: 800,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    layout: 'fit',
    maximizable: true,
    tools: [{
       type: 'refresh',
       tooltip: 'Refresh',
       scope: this,
       handler: function(){contactStore.reload();}
    }],
    items: [contactInfoGrid]
});