/**
 * 文件编码选择窗口
 * @param form 窗口中要添加的Panel
 * @return
 */
var createCodeSelect = function(codeStore, params, step, code, copyStore, grid, sonStore, cb, cbparams) {
    this.win = new Ext.Window({
        xtype: "window",
        border:0,
        title: '物料编码选择',
        height: 400,
        width: 800,
        modal: true,
        layout: 'fit',
        items: [createCodeSelectGrid(codeStore, params, step, code, copyStore, grid, sonStore, cb, cbparams)],
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
 * 物料编码选择Form
 * @param codeStore grid Store
 * @param params 返回值参数，k => v数组类型，k代表store中的名称，v代表调用此grid的form中需要返回的字段
 * @return
 */
function createCodeSelectGrid(codeStore, params, step, code, copyStore, grid, sonStore, cb, cbparams) {
    this.panel = Ext
            .create(
            'Ext.grid.Panel',
            {
                store: codeStore,
                border:0,
                selType: 'checkboxmodel',
                columnLines: true,
                tbar: [{
                        xtype: 'textfield',
                        id: 'search_code10',
                        emptyText: '物料号...',
                        listeners: {
                            specialKey: function(field, e) {
                                if (e.getKey() == Ext.EventObject.ENTER) {
                                    var search_code = Ext.getCmp('search_code10').getValue();
                                    var search_description = Ext.getCmp('search_description10').getValue();

                                    codeStore.load({
                                        params: {
                                            search_code: search_code,
                                            search_description: search_description,
                                            search_state: 1
                                        }
                                    });
                                }
                            }
                        }
                    }, {
                        xtype: 'textfield',
                        id: 'search_description10',
                        emptyText: '描述...',
                        listeners: {
                            specialKey: function(field, e) {
                                if (e.getKey() == Ext.EventObject.ENTER) {
                                    var search_code = Ext.getCmp('search_code10').getValue();
                                    var search_description = Ext.getCmp('search_description10').getValue();

                                    codeStore.load({
                                        params: {
                                            search_code: search_code,
                                            search_description: search_description,
                                            search_state: 1
                                        }
                                    });
                                }
                            }
                        }
                    }, {
                        text: '查询',
                        iconCls: 'icon-search',
                        handler: function() {
                            var search_code = Ext.getCmp('search_code10').getValue();
                            var search_description = Ext.getCmp('search_description10').getValue();

                            codeStore.load({
                                params: {
                                    search_code: search_code,
                                    search_description: search_description,
                                    search_state: 1
                                }
                            });
                        }
                    }, {
                        text: '<font color="blue">选择</font>',
                        formBind: true,
                        handler: function() {
                            var selection = panel.getView().getSelectionModel().getSelection();

                            if (selection.length > 0) {
                            	var pid = "";
                            	if(params != null && params != undefined) {
                            		if(params['pid'])
                            		    pid = params['pid'];
                            	}
                            	if(grid) {
                                    //设定grid值
                                    var record = grid.getView().getSelectionModel().getLastSelected();
                                    var replace = record.get('replace');
                                    if(replace) replace += "," + selection[0].data.code;
                                    else replace = selection[0].data.code;
                                    record.set('replace', replace);
                            	} else if(copyStore != null && copyStore != undefined) {
                            		for(i = 0; i < selection.length;i++) {
                            			var flg = false;
                            			copyStore.each(function(e) {
                                			if(e.get("code") == selection[i].get('code')) {
                                				flg = true;
                                			}
                                		});
                            			if(!flg) {
                            				if(pid) {
                            				    selection[i].data.pid = pid;
                            				}
                                		    copyStore.insert(0, selection[i]);
                                		    if(sonStore != null && sonStore != undefined) {
                                		    	selection[i].data.sid = "" + selection[i].data.id + pid;
                                		    	sonStore.insert(0, selection[i]);
                                		    	codeStore.removeAll();
                                		    }
                            			}
                            		}
                            	} else if(params) {
	                                var id = selection[0].get('id');
	                                Ext.Msg.wait('加载中，请稍后...', '提示');
	                                Ext.Ajax.request({
	                                    url: getRootPath() + '/public/product/desc/getone',
	                                    params: {id: id},
	                                    method: 'POST',
	                                    success: function(response, options) {
	                                        var data = Ext.JSON.decode(response.responseText);
	                                        if (data) {
	                                            if (params && params.constructor == Array) {
	                                                var record = selection[0];
	                                                for (var k in params) {
	                                                    var v = params[k];
	                                                    var field = Ext.getCmp(k);
	                                                    if (field != undefined)
	                                                        field.setValue(record.get(v));
	                                                }
	                                            }
	                                            Ext.Msg.hide();
	                                        } else {
	                                            Ext.MessageBox.alert('错误', '物料信息获取失败');
	                                        }
	                                    },
	                                    failure: function() {
	                                        Ext.MessageBox.alert('错误', '物料信息获取失败');
	                                    }
	                                });
                            	}
                                this.up('window').close();
                                if (cb !== null && cb !== undefined && typeof(cb) == "function" ) {
                                	if(cbparams) {
                                		cb(selection[0].data.id, cbparams);
                                	} else {
                                		cb(selection[0].data.id);
                                	}
                                }
                            } else {
                                Ext.MessageBox.alert('错误', '没有选择对象！');
                            }

                        }
                    }, {
                        text: '取消',
                        handler: function() {
                            this.up('window').close();
                        }
                    }],
                columns: [{
                        text: 'ID',
                        flex: .5,
                        hidden: true,
                        dataIndex: 'id'
                    }, {
                        text: '物料号',
                        flex: 1.8,
                        sortable: true,
                        dataIndex: 'code'
                    }, {
                        text: '物料类别',
                        flex: 2,
                        dataIndex: 'type_name',
                        renderer: showTitle
                    }, {
                        text: '物料名称',
                        flex: 2,
                        dataIndex: 'name',
                        renderer: showTitle
                    }, {
                        text: '描述',
                        flex: 2,
                        dataIndex: 'description',
                        renderer: showTitle
                    }, {
                        text: '备注',
                        flex: 2,
                        dataIndex: 'remark1',
                        renderer: showTitle
                    }]
            });

    return panel;
}