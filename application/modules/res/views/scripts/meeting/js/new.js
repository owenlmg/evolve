Ext.define('comboGrid', {
    extend: 'Ext.form.ComboBox',
    requires: ['Ext.grid.Panel'],
    alias: ['widget.comboGrid'],
    // copied from ComboBox 
    createPicker: function() {
        var me = this,
        picker,
        menuCls = Ext.baseCSSPrefix + 'menu',
        opts = Ext.apply({
            selModel: {
                mode: me.multiSelect ? 'SIMPLE' : 'SINGLE'
            },
            floating: true,
            hidden: true,
            ownerCt: me.ownerCt,
            cls: me.el.up('.' + menuCls) ? menuCls : '',
            store: me.store,
            displayField: me.displayField,
            focusOnToFront: false,
            pageSize: me.pageSize
        }, me.listConfig, me.defaultListConfig);

    // NOTE: we simply use a grid panel
    //picker = me.picker = Ext.create('Ext.view.BoundList', opts);
    picker = me.picker = Ext.create('Ext.grid.Panel', opts);
    // hack: pass getNode() to the view
    picker.getNode = function() {
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

var booleanRender = function(val){
	if(val){
		return '<img src="'+homePath+'/public/images/icons/ok.png"></img>';
	}
}

var meetingForm = Ext.create('Ext.form.Panel', {
	id: 'meetingForm',
	layout: 'form',
	border: 0,
    url: homePath+'/public/res/meeting/new',
    bodyPadding: '2 2 0',
    fieldDefaults: {
        msgTarget: 'side',
        labelAlign: 'right',
        labelWidth: 70,
        labelStyle: 'font-weight:bold'
    },
    items: [{
    	xtype: 'hiddenfield',
    	name: 'operate'
    }, {
    	xtype: 'hiddenfield',
    	name: 'id'
    }, {
    	xtype: 'hiddenfield',
    	name: 'members_id'
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
        	flex: 2,
            xtype:'comboGrid',
            displayField: 'name',
            valueField: 'id',
            triggerAction: 'all',
            lazyRender: true,
            name: 'room_id',
            fieldLabel: '会议室',
            afterLabelTextTpl: required,
            editable: false,
            allowBlank: false,
            anchor:'100%',
            store: roomStore,
            listConfig: {
            	width: 400,
           	 	columns: [
           	 	          {header: '名称',width: 150,dataIndex: 'name'},
           	 	          {header: '投影仪',width: 60,dataIndex: 'projector',align: 'center',renderer: booleanRender},
           	 	          {header: '电话',width: 120,dataIndex: 'tel'},
           	 	          {header: '容纳人数',width: 100,dataIndex: 'qty'}
                ]
            }
        }, {
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [{
                xtype:'comboGrid',
                displayField: 'cname',
                valueField: 'id',
                triggerAction: 'all',
                lazyRender: true,
                name: 'moderator',
                fieldLabel: '主持人',
                afterLabelTextTpl: required,
                editable: false,
                allowBlank: false,
                anchor:'100%',
                store: employeeListStore,
                listConfig: {
                	layout: 'fit',
                	selType: 'checkboxmodel',
               	 	columns: [
               	 	          {header: '工号',flex: 1,dataIndex: 'number'},
               	 	          {header: '中文名',flex: 1,dataIndex: 'cname'}
                    ]
                }
            }]
        }]
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
        	flex: 2,
        	xtype: 'textfield',
            allowBlank: false,
            border: false,
            name: 'subject',
            fieldLabel: '主题',
            anchor: '100%'
        }, {
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [
				Ext.create('Go.form.field.DateTime',{
				    renderTo:Ext.getBody(),
					fieldLabel:'时间从',
					name: 'time_from',
					format:'Y-m-d H:i:s',
					editable: false,
					value: Ext.util.Format.date(new Date(), 'Y-m-d H:00:00'),
				    allowBlank: false,
				    anchor:'100%'
				})
            ]
        }]
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
        	flex: 2,
            xtype:'comboGrid',
            displayField: 'cname',
            valueField: 'id',
            triggerAction: 'all',
            lazyRender: true,
            name: 'members',
            fieldLabel: '参会人员',
            afterLabelTextTpl: required,
            editable: false,
            allowBlank: false,
            anchor:'100%',
            multiSelect: true,
            store: employeeListStore,
            listConfig: {
            	layout: 'fit',
            	id: 'select_members_grid',
            	selType: 'checkboxmodel',
           	 	columns: [
           	 	          {header: 'ID',flex: 1,dataIndex: 'id',hidden: true},
           	 	          {header: '工号',flex: 1,dataIndex: 'number'},
           	 	          {header: '中文名',flex: 1,dataIndex: 'cname'},
           	 	          {header: '英文名',flex: 1,dataIndex: 'ename'}
                ]
            },
            listeners: {
            	change: function( sel, newValue, oldValue, eOpts ){
            		this.up('form').getForm().findField('members_id').setValue(newValue);
            	}
            }
        }, {
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [
				Ext.create('Go.form.field.DateTime',{
				    renderTo:Ext.getBody(),
					fieldLabel:'时间至',
					name: 'time_to',
					format:'Y-m-d H:i:s',
					editable: false,
					value: Ext.util.Format.date(new Date(), 'Y-m-d H:00:00'),
				    allowBlank: false,
				    anchor:'100%'
				})
            ]
        }]
    }, {
        xtype: 'container',
        anchor: '100%',
        layout: 'hbox',
        items:[{
            xtype: 'container',
            flex: 1,
            layout: 'anchor',
            items: [{
            	xtype: 'textareafield',
                name: 'remark',
                fieldLabel: '备注',
                height: 100,
                enableFont: false,
                anchor: '100%'
            }]
        }]
    }],
    buttons: [{
        text: '提交',
        id: 'meetingSubmit',
        handler: function() {
        	var form = this.up('form').getForm();
        	var now = new Date();
        	
        	var time_from = form.findField('time_from').getValue();
        	var time_to = form.findField('time_to').getValue();

        	var date_from = Ext.util.Format.date(time_from, 'Y-m-d');
        	var date_to = Ext.util.Format.date(time_to, 'Y-m-d');
        	
        	if(time_from < now){
        		Ext.MessageBox.alert('错误', '开始时间已晚点，不能提交！');
        	}else if(time_from >= time_to){
        		Ext.MessageBox.alert('错误', '开始时间大于或等于结束时间，不能提交！');
        	}else if(date_from != date_to){
        		Ext.MessageBox.alert('错误', '日期跨天，不能提交！');
        	}else if(form.isValid()){
                Ext.MessageBox.confirm('确认', '确定提交？', function(button, text){
                    if(button == 'yes'){
                    	form.submit({
                            waitMsg: '提交中，请稍后...',
                            success: function(form, action) {
                         	    var data = action.result;
                          	    
                                if(data.success){
                                    Ext.MessageBox.alert('提示', data.info);
                                    meetingWin.hide();
                                    Ext.getCmp('meetingGrid').getView().getSelectionModel().clearSelections();
                                    meetingStore.reload();
                                }else{
                                    Ext.MessageBox.alert('错误', data.info);
                                }
                            },
                            failure: function(form, action) {
                                Ext.MessageBox.alert('错误', action.result.info);
                            }
                        });
                    }
                });
            }
        }
    }, {
        text: '取消',
        handler: function() {
            meetingWin.hide();
        }
    }]
});

var meetingWin = Ext.create('Ext.window.Window', {
    title: '会议预定',
    id: 'meetingWin',
    layout: 'fit',
    border: 0,
    width: 750,
    modal: true,
    constrain: true,
    closeAction: 'hide',
    resizable: false,
    items: [{
        region: 'center',
        split: true,
        items: [meetingForm]
    }]
});