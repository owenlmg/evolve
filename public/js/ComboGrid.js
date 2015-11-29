Ext.define('Ext.form.ComboGrid', {
    extend: 'Ext.form.ComboBox',
    requires: ['Ext.grid.Panel'],
    alias: ['widget.Ext.form.ComboGrid'],
    createPicker: function () {
    	var store = Ext.create('Ext.data.Store', {
    		pageSize:10,
            model: Ext.define('employee', {
                extend: 'Ext.data.Model',
                idProperty: 'id',
                fields: [{name: "id"},
                    {name: "number"},
                    {name: "cname"},
                    {name: "email"}
                ]
            }),
            proxy: {
                type: 'ajax',
                reader: {
                    root: 'rows',
                    totalProperty: 'total'
                },
                url: getRootPath() + '/public/hra/employee/getEmployeeforsel'
            },
            autoLoad: true,
            listeners : {
            	beforeload : function() {
                    var search_key = Ext.getCmp('search_employee_key').getValue();
                    Ext.apply(store.proxy.extraParams, {
                    	search_key: search_key
                    });
            		
            	}
            }
        });
    	
    	var listConfig = {
    			tbar: [{
                    xtype: 'textfield',
                    id: 'search_employee_key',
                    width: 80,
                    emptyText: '搜索...',
                    listeners: {
                    	specialKey :function(field,e){
                            if (e.getKey() == Ext.EventObject.ENTER){
                            	store.loadPage(1);
                            }
                        }
                    }
                }, {
                    text: '查询',
                    handler: function() {
                        var search_key = Ext.getCmp('search_employee_key').getValue();
                        store.baseParams = {
                        	search_key: search_key
                        };
                        store.loadPage(1);
                    }
                }, {
                    text: '确定',
                    handler: function() {
                    	picker.hide();
                    }
                }],
    	    columns: 
	        	[
		           { header: '姓名', dataIndex: 'cname'}, 
		           { header: '工号', dataIndex: 'number'}
	        	],
        	bbar: Ext.create('Ext.PagingToolbar', {
                store: store,
                displayInfo: true,
                displayMsg: '显示 {0} - {1} 共 {2}',
                emptyMsg: "没有数据"
            })
        }
        var me = this,
            picker, menuCls = Ext.baseCSSPrefix + 'menu',
            opts = Ext.apply({
                    selModel: {
                        mode: me.multiSelect ? 'SIMPLE' : 'SINGLE'
                    },
                    floating: true,
                    hidden: true,
                    ownerCt: me.ownerCt,
                    cls: me.el.up('.' + menuCls) ? menuCls : '',
                    store: store,
                    displayField: me.displayField,
                    focusOnToFront: true,
                    pageSize: me.pageSize
                },
                me.listConfig ? me.listConfig : listConfig, me.defaultListConfig);
        // NOTE: we simply use a grid panel
        // picker = me.picker = Ext.create('Ext.view.BoundList', opts);
        picker = me.picker = Ext.create('Ext.grid.Panel', opts);
        // hack: pass getNode() to the view
        picker.getNode = function () {
            picker.getView().getNode(arguments);
        };
        me.mon(picker, {
            itemclick: me.onItemClick,
            refresh: me.onListRefresh,
            scope: me
        });
        me.mon(picker.getSelectionModel(), {
            selectionChange: me.onListSelectionChange,
            scope: me
        });
        return picker;
    }
});