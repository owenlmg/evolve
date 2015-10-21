Ext.define('Contacts', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: "id"},
             {name: "type"},
             {name: "type_name"},
             {name: "photo_path"},
             {name: "number"},
             {name: "cname"},
             {name: "ename"},
             {name: "tel"},
             {name: "ext"},
             {name: "email"},
             {name: "dept"},
             {name: "post"},
             {name: "manager"},
             {name: "dept_manager"}]
});

//用工形式
var employmentTypeListStore = Ext.create('Ext.data.Store', {
 model: 'Selection',
 proxy: {
     type: 'ajax',
     reader: 'json',
     url: homePath+'/public/hra/employee/gettype/option/list'
 },
 autoLoad: true
});

var deptListStore = Ext.create('Ext.data.Store', {
    model: 'Selection',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/hra/contact/getdeptlist'
    }
});

var contactsStore = Ext.create('Ext.data.Store', {
    model: 'Contacts',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath + '/public/hra/contact/getcontacts'
    },
    listeners: {
    	beforeload: function(store){
    		Ext.apply(store.proxy.extraParams, {
                type	: Ext.getCmp('search_contacts_type').getValue(),
                dept	: Ext.getCmp('search_contacts_dept').getValue(),
                key  	: Ext.getCmp('search_contacts_key').getValue()
            });
    	}
    }
});

var contactsGrid = Ext.create('Ext.grid.Panel', {
    columnLines: true,
    store: contactsStore,
    layout: 'fit',
    tbar: [{
        xtype: 'combobox',
        id: 'search_contacts_type',
        value: 1,
        editable: false,
        displayField: 'name',
        valueField: 'id',
        query: 'local',
        width: 120,
        store: employmentTypeListStore,
        listeners: {
            change: function(){
                contactsStore.load();
            }
        }
    }, {
        xtype: 'combobox',
        id: 'search_contacts_dept',
        emptyText: '部门...',
        displayField: 'name',
        valueField: 'id',
        width: 180,
        store: deptListStore,
        queryMode: 'local',
        listeners: {
            change: function(){
                contactsStore.load();
            }
        }
    }, {
        xtype: 'textfield',
        id: 'search_contacts_key',
        emptyText: '关键字...',
        width: 100,
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	contactsStore.load();
                }
            }
        }
    }, {
    	text: '查询',
    	iconCls: 'icon-search',
    	handler: function(){
    		contactsStore.load();
    	}
    }, '->', {
    	text: '刷新',
    	iconCls: 'icon-refresh',
    	handler: function(){
    		contactsStore.load();
    	}
    }],
    columns: [{
        xtype: 'rownumberer'
    }, {
		text: '用工形式',
		hidden: true,
		align: 'center',
		dataIndex: 'type_name',
		width: 80
	}, {
		text: '工号',
		align: 'center',
		dataIndex: 'number',
		width: 80
	}, {
		text: '中文名',
		dataIndex: 'cname',
		width: 100
	}, {
		text: '英文名',
		dataIndex: 'ename',
		width: 150
	}, {
		text: '电话',
		dataIndex: 'tel',
		flex: 1
	}, {
		text: '分机号',
		hidden: true,
		dataIndex: 'ext',
		width: 80
	}, {
		text: '部门',
		hidden: true,
		dataIndex: 'dept',
		width: 120
	}, {
		text: '职位',
		hidden: true,
		dataIndex: 'post',
		width: 180
	}, {
		text: '部门主管',
		hidden: true,
		dataIndex: 'dept_manager',
		width: 100
	}, {
		text: '直接主管',
		hidden: true,
		dataIndex: 'manager',
		width: 100
	}, {
		text: '邮箱',
		hidden: true,
		dataIndex: 'email',
		width: 180
	}],
	listeners: {
		selectionchange: function(sm, selectedRecord) {
            if (selectedRecord.length) {
                var detailPanel = Ext.getCmp('employeeDetailPanel');
                var data = selectedRecord[0].data;

                detailPanel.update(employeeTpl.apply(data));
            }
        }
	}
});

var employeeTplMarkup = [
'<table style="font-size:12px;width:100%;"><tr>',
'<td align="center" width="180" rowspan="12"><img width="180" height="220" src="{photo_path}"></img></td>',
'<td width="60" align="right"><b>用工形式: </b></td><td>{type_name}</td><tr>',
'<tr><td align="right"><b>工号: </b></td><td>{number}</td></tr>',
'<tr><td align="right"><b>中文名: </b></td><td>{cname}</td></tr>',
'<tr><td align="right"><b>英文名: </b></td><td>{ename}</td></tr>',
'<tr><td align="right"><b>电话: </b></td><td>{tel}</td></tr>',
'<tr><td align="right"><b>分机号: </b></td><td>{ext}</td></tr>',
'<tr><td align="right"><b>部门: </b></td><td>{dept}</td></tr>',
'<tr><td align="right"><b>职位: </b></td><td>{post}</td></tr>',
'<tr><td align="right"><b>部门主管: </b></td><td>{dept_manager}</td></tr>',
'<tr><td align="right"><b>直接主管: </b></td><td>{manager}</td></tr>',
'<tr><td align="right"><b>邮箱: </b></td><td>{email}</td></tr>',
'</table>'
];

var employeeTpl = Ext.create('Ext.Template', employeeTplMarkup);

var contactsWin = Ext.create('Ext.window.Window', {
	title: '通讯录',
	id: 'contactsWin',
	height: 300,
	width: 1100,
	modal: true,
	constrain: true,
	border: 0,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	layout: 'border',
	items: [{
		region: 'center',
		border: 0,
		layout: 'fit',
		items: contactsGrid
	}, {
		title: '人员信息',
		region: 'east',
		id: 'employeeDetailPanel',
		width: 460,
		split: true,
		collapsible: true,
		bodyPadding: 2,
		tpl: [
            '中文名: <a href="{}" target="_blank">{cname}</a><br/>',
            '中文名: {cname}<br/>',
            '英文名: {ename}<br/>'
        ]
	}]
});