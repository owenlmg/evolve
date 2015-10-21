// 年假库数据模型
Ext.define('VStorage', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "number"}, 
             {name: "employee_id"}, 
             {name: "employee_type"}, 
             {name: "dept"}, 
             {name: "cname"}, 
             {name: "ename"}, 
             {name: "entry_date"}, 
             {name: "regularization_date"}, 
             {name: "in_year_qty"}, 
             {name: "qty", type: 'int'}, 
             {name: "qty_used"}, 
             {name: "qty_left"}, 
             {name: "limit_to_qty"}, 
             {name: "limit_to_date"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}]
});

// 年假库数据源
var vstorageStore = Ext.create('Ext.data.Store', {
    model: 'VStorage',
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath+'/public/hra/attendance/getvacationstorage/option/list'
    },
    listeners: {
    	beforeload: function(){
    		var key = Ext.getCmp('search_vstorage_key').getValue();
            
    		Ext.apply(vstorageStore.proxy.extraParams, {
        		key: key
            });
        }
    }
});

// 年假库编辑插件
var vstorageRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

if(hraBtnHidden){
	vstorageRowEditing = null;
}

// 年假库列表
var vstorageGrid = Ext.create('Ext.grid.Panel', {
    title: '年假库',
    border: 0,
    id: 'vstorageGrid',
    store: vstorageStore,
    selType: 'checkboxmodel',
    columnLines: true,
    tbar: [{
        xtype: 'textfield',
        id: 'search_vstorage_key',
        emptyText: '工号/中文名/英文名...',
        listeners: {
            specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	vstorageStore.loadPage(1);
                }
            }
        }
    }, {
        xtype: 'splitbutton',
        text: '查询',
        iconCls: 'icon-search',
        handler: function(){
        	vstorageStore.loadPage(1);
        },
        menu: [{
            text: '导出',
            hidden: hraBtnHidden,
            iconCls: 'icon-export',
            handler: function(){
                window.open(homePath+'/public/hra/attendance/getvacationstorage/option/csv');
            }
        }]
    }, {
        text: '刷新年假库',
        hidden: hraBtnHidden,
        iconCls: 'icon-refresh',
        menu: [{
        	text: '全部',
        	iconCls: 'icon-group',
        	handler: function(){
        		Ext.MessageBox.show({
        			title:'确认刷新年假库',
        			msg: '刷新年假库将根据员工转正日期计算其最近一年的年假天数。<br><b>是否覆盖</b>所有员工最近一年已有年假？',
			   		buttons: Ext.MessageBox.YESNOCANCEL,
			   		fn: function(button){
			   			if(button != 'cancel'){
			   				var cover = 0;
			   				
			   				if(button == 'yes'){
			   					cover = 1;
			   				}
			   				
	                        Ext.Msg.wait('提交中，请稍后...', '提示');
	                        Ext.Ajax.request({
	                            url: homePath+'/public/hra/attendance/refreshvacationstorage',
	                            params: {cover: cover},
	                            method: 'POST',
	                            success: function(response, options) {
	                                var data = Ext.JSON.decode(response.responseText);

	                                if(data.success){
	                                    Ext.MessageBox.alert('提示', data.info);
	                                    vstorageStore.reload();
	                                }else{
	                                    Ext.MessageBox.alert('错误', data.info);
	                                }
	                            },
	                            failure: function(response){
	                                Ext.MessageBox.alert('错误', '保存提交失败');
	                            }
	                        });
			   			}
			   		},
			   		icon: Ext.MessageBox.QUESTION
        		});
            }
        }, {
        	text: '个人',
        	iconCls: 'icon-user',
        	handler: function(){
        		var selection = Ext.getCmp('vstorageGrid').getView().getSelectionModel().getSelection();

                if(selection.length != 1){
                	Ext.MessageBox.alert('错误', '请选择单个员工！');
                }else{
                	Ext.MessageBox.show({
            			title:'确认刷新年假库',
            			msg: '刷新年假库将根据员工转正日期计算其最近一年的年假天数。<br><b>是否覆盖</b>当前员工最近一年已有年假？',
    			   		buttons: Ext.MessageBox.YESNOCANCEL,
    			   		fn: function(button){
    			   			if(button != 'cancel'){
    			   				var cover = 0;
    			   				
    			   				if(button == 'yes'){
    			   					cover = 1;
    			   				}
    			   				
    			   				Ext.Msg.wait('提交中，请稍后...', '提示');
                                Ext.Ajax.request({
                                    url: homePath+'/public/hra/attendance/refreshvacationstorage',
                                    params: {cover: cover, employee_id: selection[0].get('employee_id')},
                                    method: 'POST',
                                    success: function(response, options) {
                                        var data = Ext.JSON.decode(response.responseText);

                                        if(data.success){
                                            Ext.MessageBox.alert('提示', data.info);
                                            vstorageStore.reload();
                                        }else{
                                            Ext.MessageBox.alert('错误', data.info);
                                        }
                                    },
                                    failure: function(response){
                                        Ext.MessageBox.alert('错误', '保存提交失败');
                                    }
                                });
    			   			}
    			   		},
    			   		icon: Ext.MessageBox.QUESTION
            		});
                }
            }
        }]
    }, {
        text: '保存修改',
        hidden: hraBtnHidden,
        iconCls: 'icon-save',
        scope: this,
        handler: function(){
            var updateRecords = vstorageStore.getUpdatedRecords();
            
            // 判断是否有修改数据
            if(updateRecords.length > 0){
                var changeRows = {
                        updated:    []
                }
                
                for(var i = 0; i < updateRecords.length; i++){
                    changeRows.updated.push(updateRecords[i].data)
                }
                
                Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                    if(button == 'yes'){
                        var json = Ext.JSON.encode(changeRows);
                        
                        Ext.Msg.wait('提交中，请稍后...', '提示');
                        Ext.Ajax.request({
                            url: homePath+'/public/hra/attendance/editvacationstorage',
                            params: {json: json},
                            method: 'POST',
                            success: function(response, options) {
                                var data = Ext.JSON.decode(response.responseText);
         
                                if(data.success){
                                    Ext.MessageBox.alert('提示', data.info);
                                    vstorageStore.reload();
                                }else{
                                    Ext.MessageBox.alert('错误', data.info);
                                }
                            },
                            failure: function(response){
                                Ext.MessageBox.alert('错误', '保存提交失败');
                            }
                        });
                    }
                });
            }else{
                Ext.MessageBox.alert('提示', '没有修改任何数据！');
            }
        }
    }],
    plugins: vstorageRowEditing,
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
        	if(record.get('qty_left') == 0){
        		return 'gray-row';
            }
        }
    },
    columns: [{
        xtype: 'rownumberer'
    }, {
        text: '用工形式',
        align: 'center',
        dataIndex: 'employee_type',
        flex: 1
    }, {
        text: '工号',
        align: 'center',
        dataIndex: 'number',
        flex: 1
    }, {
        text: '部门',
        dataIndex: 'dept',
        flex: 1
    }, {
        text: '中文名',
        dataIndex: 'cname',
        flex: 1
    }, {
        text: '英文名',
        dataIndex: 'ename',
        flex: 1
    }, {
        text: '入职日期',
        align: 'center',
        dataIndex: 'entry_date',
        flex: 1
    }, {
        text: '转正日期',
        align: 'center',
        dataIndex: 'regularization_date',
        flex: 1
    }, {
        text: '入司年数',
        align: 'center',
        dataIndex: 'in_year_qty',
        flex: 1
    }, {
        text: '年假天数 *',
        align: 'center',
        editor: 'numberfield',
        dataIndex: 'qty',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #DFFFDF';
        	return val;
        },
        flex: 1
    }, {
        text: '已使用天数 *',
        align: 'center',
        editor: 'numberfield',
        dataIndex: 'qty_used',
        renderer: function(val, meta, record){
        	meta.style = 'background-color: #FFFFDF';
        	return val;
        },
        flex: 1
    }, {
        text: '剩余天数',
        align: 'center',
        dataIndex: 'qty_left',
        flex: 1
    }, {
    	text: '到期日期',
    	align: 'center',
    	dataIndex: 'limit_to_date',
    	renderer: function(val, meta, record){
            if(record.get('limit_to_qty') > 0 && record.get('limit_to_qty') <= 30){
                meta.style = 'background-color: #ffe2e2';
            }
            
            return val;
        },
    }, {
        text: '有效期限',
        hidden: true,
        align: 'center',
        dataIndex: 'limit_to_qty',
        renderer: function(val, meta){
            if(val > 0 && val <= 30){
                meta.style = 'background-color: #ffe2e2';
            }
            
            return val;
        },
        flex: 1
    }, {
        text: '备注 *',
        dataIndex: 'remark',
        editor: 'textfield',
        flex: 1
    }, {
        text: '创建人',
        hidden: true,
        dataIndex: 'creater',
        align: 'center',
        flex: 1
    }, {
        text: '创建时间',
        hidden: true,
        dataIndex: 'create_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        flex: 1.5
    }, {
        text: '更新人',
        hidden: true,
        dataIndex: 'updater',
        align: 'center',
        flex: 1
    }, {
        text: '更新时间',
        hidden: true,
        dataIndex: 'update_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
        flex: 1.5
    }]
});