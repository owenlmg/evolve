Ext.define('Ext.ux.EmployeeCombo', {
    extend: 'Ext.ux.form.field.BoxSelect',
    alias: 'widget.employeebobox',
    xtype: 'employeebobox',
    initComponent: function(){
    	var employeeSearchStore = Ext.create('Ext.data.Store', {
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
            autoLoad: true
        });

    	var me = this;
        Ext.apply(me, {
            store: employeeSearchStore,
            valueField: 'id',
            displayField: 'cname',
            labelWidth: 80,
            delimiter: ',',
        	editable: true,
            typeAhead: true,
            hideTrigger:true,
            queryMode: 'local',
	        queryParam: 'search_key',
	        minChars: 1,
	        listConfig: {
    			loadingText: '搜索中...',
                emptyText: '未找到匹配数据',
                getInnerTpl: function() {
                    return '【{number}】{cname}';
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
                },
                beforequery : function(e){
                    var combo = e.combo;
                    if(!e.forceAll){ 
                        var value = e.query; 
                        combo.store.filterBy(function(record,id){
	                        var text = record.get(combo.displayField); 
	                        //用自己的过滤规则,如写正则式 
	                        if(text) {
	                        	return (text.indexOf(value)!=-1);
	                        }
	                    });
	                    combo.expand();  
	                    return false; 
                    } 
                }
            } 
        });
    	this.callParent();
    }
});