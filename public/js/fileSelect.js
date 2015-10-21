/*
 * 文件查看公共模块
 * 调用方法：
 *    var FileSelect = lib.dcc.FileSelect({grid: grid});
 *    FileSelect.show();
 */
var lib = lib || {};
lib.dcc = lib.dcc || {};
(function(window, undefined) {

    lib.dcc.FileSelect = function(opts) {
        return new FileSelect(opts);
    };
    var FileSelect = function(opts) {
        // 1 创建Grid
        var store = opts.store;
        var returnId = opts.returnId;
        var returnValue = opts.returnValue;
        var grid = opts.grid;
        var userid = opts.userid;

        this.gridPanel = createUploadSelect.call(this, store, returnId, returnValue, grid, userid);
        this.win = createWinUpload.call(this, this.gridPanel);
    };

    FileSelect.prototype = {
        show: function() {
            this.win.show();
        }
    };

    function getStore() {
        var store = Ext.create('Ext.data.Store', {
            model: 'upload',
            proxy: {
                type: 'ajax',
                reader: 'json',
                url: getRootPath() + '/public/dcc/upload/getfiles/type/1'
            },
            autoLoad: true
        });
        return store;
    }

    // 查看详情
    function createForm(uploadStore) {
        var form = new Ext.form.Panel({
            width: 500,
            border:0,
            bodyPadding: '10 10 0',
            defaults: {
                anchor: '100%',
                msgTarget: 'side',
                labelWidth: 85
            },
            items: [{
                    xtype: 'textfield',
                    name: 'id',
                    hidden: true
                }, {
                    xtype: 'textfield',
                    id: 'employeeId',
                    name: 'employeeId',
                    hidden: true
                }, {
                    xtype: 'textfield',
                    id: 'deptId',
                    name: 'deptId',
                    hidden: true
                }, {
                    region: 'center',
                    id: 'uploadPanel',
                    xtype: 'panel',
                    border : 0,
                    items: [{
                        html: '<input id="uploadFiles" type="file" name="uploadFiles" multiple="true" /><div id="fileQueue" class="fileQueue"></div><input id="upload" type="hidden" name="upload" value="上传" onclick="javascript:$(\'#uploadFiles\').uploadify(\'upload\',\'*\')" />'
                    }],
                    listeners: {
                        afterrender : function() {
                        	uploadify($("#uploadFiles"));
                        }
                    }
                }, Ext.create("Ext.ux.comboboxtree", {
                    id: 'share_name',
                    name: 'share_name',
                    hiddenName: 'employeeId',
                    editable: false,
                    store: Ext.create('Ext.data.TreeStore', {
                        proxy: {
                            type: 'ajax',
                            url: getRootPath() + '/public/dcc/upload/gettree',
                            actionMethods: 'post'
                        },
                        sorters: [{
                                property: 'leaf',
                                direction: 'ASC'
                            },
                            {
                                property: 'text',
                                direction: 'ASC'
                            }]
                    }),
                    cascade: 'child', //级联方式:1.child子级联;2.parent,父级联,3,both全部级联
                    //        checkModel:'single',//当json数据为不带checked的数据时只配置为single,带checked配置为double为单选,不配置为多选
                    width: 270,
                    onlyLeaf: true, //是否只选择叶子节点
                    fieldLabel: '共享给个人',
                    labelWidth: 85,
                    displayField: 'text',
                    valueField: 'id',
                    rootId: '0',
                    rootText: 'DRP',
                    treeNodeParameter: ''
                }),
                Ext.create("Ext.ux.comboboxtree", {
                    id: 'share_dept',
                    name: 'share_dept',
                    hiddenName: 'deptId',
                    editable: false,
                    store: Ext.create('Ext.data.TreeStore', {
                        proxy: {
                            type: 'ajax',
                            url: getRootPath() + '/public/dcc/upload/gettree/method/dept',
                            actionMethods: 'post'
                        },
                        sorters: [{
                                property: 'leaf',
                                direction: 'ASC'
                            },
                            {
                                property: 'text',
                                direction: 'ASC'
                            }]
                    }),
                    cascade: 'child', //级联方式:1.child子级联;2.parent,父级联,3,both全部级联
                    //	        checkModel:'single',//当json数据为不带checked的数据时只配置为single,带checked配置为double为单选,不配置为多选
                    width: 270,
                    onlyLeaf: false, //是否只选择叶子节点
                    fieldLabel: '共享给部门',
                    labelWidth: 85,
                    displayField: 'text',
                    valueField: 'id',
                    rootId: '0',
                    rootText: 'DRP',
                    treeNodeParameter: ''
                }), {
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    name: 'share_time_begin',
                    fieldLabel: '共享时间从',
                    value: new Date()
                }, {
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    name: 'share_time_end',
                    fieldLabel: '共享时间至'
                }, {
                    xtype: 'textarea',
                    fieldLabel: '描述',
                    name: 'description'
                }, {
                    xtype: 'textarea',
                    fieldLabel: '备注',
                    name: 'remark'
                }],
            buttons: [{
                    text: '保存',
                    handler: function() {
                        var form = this.up('form').getForm();
                        var win = this.up('window');
                        if (form.isValid()) {
                            form.submit({
                                url: getRootPath() + '/public/dcc/upload/save',
                                waitMsg: '提交中，请稍后...',
                                success: function(form, action) {
                                    if (action.result.success) {
                                        Ext.MessageBox.alert('提示', action.result.info);
                                        form.reset();
                                        uploadStore.load();
                                        win.close();
                                    } else {
                                        Ext.MessageBox.alert('错误', action.result.info);
                                    }
                                },
                                failure: function(form, action) {
                                    Ext.MessageBox.alert('错误', action.result.info);
                                }
                            });
                        }
                    }
                }, {
                    text: '重置',
                    handler: function() {
                        this.up('form').getForm().reset();
                    }
                }, {
                    text: '取消',
                    handler: function() {
                        this.up('window').close();
                    }
                }]
        });
        return form;
    }

    // 实体文件选择grid
    function createUploadSelect(uploadStore, returnId, returnValue, grid, userid) {
        if (uploadStore === null) {
            uploadStore = this.uploadStore;
        }
        var uploadSelect = Ext.create('Ext.grid.Panel', {
            store: uploadStore,
            border:0,
            selType: 'checkboxmodel',
            columnLines: true,
            tbar: [{
                    xtype: 'textfield',
                    id: 'search_name2',
                    emptyText: '文件号...',
                    listeners: {
                    	specialKey :function(field,e){
                            if (e.getKey() == Ext.EventObject.ENTER){
                            	uploadStore.loadPage(1);
                            }
                        }
                    }
                }, {
                    xtype: 'textfield',
                    id: 'search_description2',
                    emptyText: '描述...',
                    listeners: {
                    	specialKey :function(field,e){
                            if (e.getKey() == Ext.EventObject.ENTER){
                            	uploadStore.loadPage(1);
                            }
                        }
                    }
                }, {
                    text: '查询',
                    iconCls: 'icon-search',
                    handler: function() {
                    var search_name = Ext.getCmp('search_name2').getValue();
                    var search_description = Ext.getCmp('search_description2').getValue();

                    uploadStore.baseParams = {
                            search_name: search_name,
                            search_description: search_description,
                            search_del: 0
                    }
                    uploadStore.loadPage(1);
                }
                }, {
                    text: '<font color="blue">选择</font>',
                    formBind: true,
                    handler: function() {
                        var selection = uploadSelect.getView().getSelectionModel().getSelection();
                        if (selection.length > 0) {
                            var name = "";
                            var id = "";
                            for (var i = 0; i < selection.length; i++) {
                                if (name) {
                                    name += " | ";
                                    id += ",";
                                }
                                name += selection[i].get('name');
                                id += selection[i].get('id');
                            }

                            if (grid === undefined || grid == null || grid == "") {
                                var idField = Ext.getCmp(returnId);
                                var valueField = Ext.getCmp(returnValue);
                                if (idField !== undefined) {
                                    idField.setValue(id);
                                }
                                if (valueField !== undefined) {
                                    valueField.setValue(name);
                                }
                            } else {
                                //设定grid值
                                var record = grid.getView().getSelectionModel().getLastSelected();
                                record.set(returnId, id);
                                record.set(returnValue, name);
                            }
                            this.up('window').close();
                        } else {
                            Ext.MessageBox.alert('错误', '没有选择对象！');
                        }

                    }
                }, {
                    text: '上传',
                    handler: function() {
                    	lib.dcc.uploadify({store: uploadStore, userid : userid}).show();
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
                    text: '文件名',
                    flex: 1.5,
                    sortable: true,
                    dataIndex: 'name'
                }, {
                    text: '文件类型',
                    flex: 1,
                    sortable: true,
                    dataIndex: 'type'
                }, {
                    text: '共享给',
                    flex: 1.5,
                    dataIndex: 'share_name'
                }, {
                    text: '共享期间',
                    flex: 1.5,
                    dataIndex: 'share_time'
                }, {
                    text: '描述',
                    flex: 1.5,
                    dataIndex: 'description'
                }, {
                    text: '备注',
                    flex: 1,
                    dataIndex: 'remark'
                }, {
                    text: '上传人',
                    flex: 0.8,
                    dataIndex: 'updater'
                }, {
                    text: '上传时间',
                    flex: 1,
                    hidden: true,
                    dataIndex: 'upload_time',
                    renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')
                }]
        });

        uploadStore.on("beforeload", function() {
        	var search_name = "";
        	var search_description = "";
        	var search_name2 = Ext.getCmp('search_name2');
        	if(search_name2 != null && search_name2 != undefined) {
                search_name = search_name2.getValue();
        	}
        	var search_description2 = Ext.getCmp('search_description2');
        	if(search_description2 != null && search_description2 != undefined) {
                search_description = search_description2.getValue();
        	}

            Ext.apply(uploadStore.proxy.extraParams, {
                search_name: search_name,
                search_description: search_description,
                search_del: 0
            });
        });
        return uploadSelect;
    }

    function selectFile(selection) {
        var name = "";
        var id = "";
        for (var i = 0; i < selection.length; i++) {
            if (name) {
                name += " | ";
                id += ",";
            }
            name += selection[i].get('name');
            id += selection[i].get('id');
        }

        if (this.grid === undefined) {
            var idField = Ext.getCmp(this.returnId);
            var valueField = Ext.getCmp(this.returnValue);
            if (idField !== undefined) {
                idField.setValue(id);
            }
            if (valueField !== undefined) {
                valueField.setValue(name);
            }
        } else {
            //设定grid值
            var record = this.grid.getView().getSelectionModel().getLastSelected();
            record.set(this.returnId, id);
            record.set(this.returnValue, name);
        }
        this.win.hide();
    }

    function createWinUpload(uploadSelect) {
        var win = new Ext.Window({
            xtype: "window",
            border:0,
            title: '文件选择',
            height: 400,
            width: 1000,
            modal: true,
            layout: 'fit',
            items: [uploadSelect],
            tools: [{
                    type: 'refresh',
                    tooltip: '刷新表格数据',
                    scope: this,
                    handler: function() {
                        uploadStore.reload();
                    }
                }]
        });
        return win;
    }

    function createWin(obj) {
        var win = new Ext.Window({
            xtype: "window",
            border:0,
            height: 500,
            title: '文件上传',
            layout: 'fit',
            closable: true,
            items: [obj]
        });
        return win;
    }
})(window);