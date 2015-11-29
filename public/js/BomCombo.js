Ext.define('Ext.ux.BomCombo', {
    extend: 'Ext.ux.form.field.BoxSelect',
    alias: 'widget.bombobox',
    xtype: 'bombobox',
    initComponent: function(){
    	var bomStore = Ext.create('Ext.data.Store', {
            model: Ext.define('bom', {
                extend: 'Ext.data.Model',
                idProperty: 'id',
                fields: [{name: "id"},
                    {name: "code"},
                    {name: "recordkey"},
                    {name: "ver"},
                    {name: "description"},
                    {name: "project_no"}
                ]
            }),
            proxy: {
                type: 'ajax',
                reader: {
                    root: 'rows',
                    totalProperty: 'total'
                },
                url: getRootPath() + '/public/product/bom/getbomforsel'
            },
            autoLoad: false
        });

    	var me = this;
        Ext.apply(me, {
            store: bomStore,
            valueField: 'id',
            displayField: 'code',
            labelWidth: 80,
            delimiter: ',',
        	editable: true,
            typeAhead: true,
            hideTrigger:true,
            typeAheadDelay:500,
            triggerAction:'query',
            queryMode: 'remote',
	        queryParam: 'search_key',
	        minChars: 2,
	        listConfig: {
    			loadingText: '搜索中...',
                emptyText: '未找到匹配数据',
                getInnerTpl: function() {
                    return '{code}';
                } 
    	  	},
            listeners: {
                'change': function(t, o) {
                	var fieldName, id;
                	if(me.hiddenName) {
                		fieldName = me.hiddenName;
                		id = Ext.getCmp(fieldName);
                	} else {
                		fieldName = me.name;
                		if(fieldName) {
                    		id = Ext.getCmp(fieldName + "_id");
                		}
                	}
                	if(id) {
            			id.setValue(o);
                	}
                }
            } 
        });
    	this.callParent();
    }
});