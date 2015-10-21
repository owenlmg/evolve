/**
 * 文件编码选择窗口
 * @param form 窗口中要添加的Panel
 * @return
 */
var createFileCodeSelect = function(codeStore, params, step, grid, code, multi, cb) {
    this.win = new Ext.Window({
        xtype: "window",
        border:0,
        title: '文件编码选择',
        height: 400,
        width: 800,
        modal: true,
        layout: 'fit',
        items: [createFileCodeSelectGrid(codeStore, params, step, grid, code, multi, cb)],
        tools: [{
                type: 'refresh',
                tooltip: '刷新表格数据',
                scope: this,
                handler: function() {
                    codeStore.reload();
                }
            }]
    });

    return win;
};

/**
 * 文件编码选择Form
 * @param codeStore grid Store
 * @param params 返回值参数，k => v数组类型，k代表store中的名称，v代表调用此grid的form中需要返回的字段
 * @return
 */
function createFileCodeSelectGrid(codeStore, params, step, grid, code, multi, cb) {
    this.panel = Ext
            .create(
            'Ext.grid.Panel',
            {
                store: codeStore,
                border:0,
                columnLines: true,
                selType: 'checkboxmodel',
                tbar: [
                    {
                        xtype: 'textfield',
                        id: 'search_code1',
                        emptyText: '文件号...',
                        listeners: {
                        	specialKey :function(field,e){
                                if (e.getKey() == Ext.EventObject.ENTER){
                                	codeStore.loadPage(1);
                                }
                            }
                        }
                    },
                    {
                        xtype: 'textfield',
                        id: 'search_description1',
                        emptyText: '描述...',
                        listeners: {
                        	specialKey :function(field,e){
                                if (e.getKey() == Ext.EventObject.ENTER){
                                	codeStore.loadPage(1);
                                }
                            }
                        }
                    },
                    {
                        text: '查询',
                        iconCls: 'icon-search',
                        handler: function() {
                            var search_code = Ext.getCmp('search_code1').getValue();
                            var search_description = Ext.getCmp('search_description1').getValue();

                            codeStore.baseParams = {
                                search_code: search_code,
                                search_description: search_description,
                                search_state: 1,
                                step: step,
                                code: code
                            }
                            codeStore.loadPage(1);
                        }
                    },
                    {
                        text: '<font color="blue">选择</font>',
                        formBind: true,
                        handler: function() {
                            var selection = panel.getView().getSelectionModel().getSelection();

                            if (selection.length > 0) {
                                if (grid) {
                                    //设定grid值
                                    var record = grid.getView().getSelectionModel().getLastSelected();
                                    record.set('code_file_code_id', selection[0].data.id);
                                    record.set('code_file_code', selection[0].data.code);
                                    record.set('newest_ver', selection[0].data.ver);
                                    record.set('project_no', selection[0].data.project_no);
                                    record.set('project_name', selection[0].data.project_name);
                                    record.set('description', selection[0].data.description);
                                }
                                // 设定返回值
                                if (params && params.constructor == Array) {
                                	for(j = 0; j < selection.length;j++) {
	                                    var record = selection[j];
	                                    for (var k in params) {
	                                        var v = params[k];
	                                        var field = Ext.getCmp(v);
	                                        if (field != undefined) {
	                                        	if(multi && multi == true) {
	                                                var sonCode = field.getValue();
	                                                if(sonCode) {
	                                                	var codeArr = sonCode.split(',');
	                                                	var exists = false;
	                                                	for(i=0;i<codeArr.length;i++) {
	                                                		if(codeArr[i] == record.get(k)) {
	                                                			exists = true;
	                                                			break;
	                                                		}
	                                                	}
	                                                	if(!exists) {
	                                                	    returnCode = sonCode + "," + record.get(k);
	                                                	}
	                                                }
	                                                else returnCode = record.get(k);
	                                        	} else {
	                                        		returnCode = record.get(k);
	                                        	}
	                                        	field.setValue(returnCode);
	                                        }
	                                    }
                                	}
                                }
                                if(cb !== null && cb !== undefined && typeof(cb) == "function" ) {
                                	cb();
                                }

                                win.close();
                                //Ext.getCmp("bom_file").focus();
                                //Ext.getCmp("bom_file").blur();
                            } else {
                                Ext.MessageBox.alert('错误',
                                        '没有选择对象！');
                            }

                        }
                    }, {
                        text: '取消',
                        handler: function() {
                            win.close();
                        }
                    }],
                columns: [
                    {
                        xtype: 'rownumberer'
                    },
                    {
                        text: 'ID',
                        flex: .5,
                        hidden: true,
                        dataIndex: 'id'
                    }, {
                        text: '文件号',
                        flex: 1.5,
                        sortable: true,
                        dataIndex: 'code'
                    },
                    {
                        text: '版本',
                        flex: .5,
                        hidden: step == 'apply',
                        sortable: true,
                        dataIndex: 'ver'
                    }, {
                        text: '项目号',
                        flex: 1.5,
                        dataIndex: 'project_name',
                        renderer: showTitle
                    },
                    {
                        text: '描述',
                        flex: 1.5,
                        dataIndex: 'description',
                        editor: 'textfield'
                    },
                    {
                        text: '备注',
                        flex: 2,
                        dataIndex: 'remark',
                        editor: 'textfield'
                    },
                    {
                        text: '创建人',
                        flex: 0.5,
                        dataIndex: 'creater'
                    },
                    {
                        text: '创建时间',
                        flex: 1,
                        hidden: true,
                        dataIndex: 'create_time',
                        renderer: Ext.util.Format
                                .dateRenderer('Y-m-d H:i:s')
                    },
                    {
                        text: '更新人',
                        flex: 0.5,
                        hidden: true,
                        dataIndex: 'updater'
                    },
                    {
                        text: '更新时间',
                        flex: 1,
                        hidden: true,
                        dataIndex: 'update_time',
                        renderer: Ext.util.Format
                                .dateRenderer('Y-m-d H:i:s')
                    }]
            });

            codeStore.on("beforeload", function() {
            	if(Ext.getCmp('search_code1')) {
            		var search_code = Ext.getCmp('search_code1').getValue();
            		var search_description = Ext.getCmp('search_description1').getValue();
            		Ext.apply(codeStore.proxy.extraParams, {
                        search_code: search_code,
                        search_description: search_description,
                        search_state: 1,
                        step: step
                    });
            	} else {
            		Ext.apply(codeStore.proxy.extraParams, {
                        search_state: 1,
                        step: step
                    });
            	}
		    });

    return panel;
}