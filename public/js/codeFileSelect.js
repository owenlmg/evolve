var codeStore = Ext.create('Ext.data.Store', {
    model: 'code',
    pageSize: 15,
    proxy: {
        type: 'ajax',
        reader: {
            root: 'topics',
            totalProperty: 'totalCount'
        },
        url: getRootPath() + '/public/dcc/code/getcode'
    },
    autoLoad: false
});

var uploadStore = Ext.create('Ext.data.Store', {
    model: 'upload',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: getRootPath() + '/public/dcc/upload/getfiles/type/1'
    },
    autoLoad: false
});

var createCodeFile = function (code_file_store, rowEditing, step, params, userid) {
    codeStore.on("beforeload", function () {
        if (Ext.getCmp('search_code1')) {
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

    var store = Ext.create('Ext.data.Store', {
        model: Ext.define('project', {
            extend: 'Ext.data.Model',
            fields: [{name: "id"},
                {name: "name"}]
        }),
        proxy: {
            type: 'ajax',
            reader: 'json',
            url: getRootPath() + '/public/dcc/code/getproject'
        },
        autoLoad: true
    });

    this.grid = Ext.create('Ext.grid.Panel', {
        xtype: 'grid',
        store: code_file_store,
        border: true,
        plugins: rowEditing,
        sortableColumns: false,
        columnLines: true,
        hideHeaders: false,
        viewConfig: {
            stripeRows: false// 取消偶数行背景色
        },
        tbar: [{
            text: '添加',
            scope: this,
            id: 'addCodeFileBtn',
            handler: function () {
                rowEditing.cancelEdit();

                var r = Ext.create('codeFile', {
                    code_file_code: '',
                    code_file_file: ''
                });

                code_file_store.insert(0, r);
                rowEditing.startEdit(0, 0);
            }
        }, {
            text: '删除',
            id: 'delCodeFileBtn',
            handler: function () {
                var selection = this.up('grid').getView().getSelectionModel().getSelection();

                if (selection.length > 0) {
                    this.up('grid').getStore().remove(selection);
                }
            }
        }],
        columns: [{
            text: '文件编码ID',
            hidden: true,
            dataIndex: 'code_file_code_id'
        }, {
            text: '文件ID',
            hidden: true,
            dataIndex: 'code_file_file_id'
        }/*, {
         text: '文件编码', dataIndex: 'code_file_code', emptyCellText: '选择文件编码', flex: 1, editor: {
         xtype: 'triggerfield',
         editable: false,
         emptyText: '选择文件编码',
         triggerCls: 'x-form-search-trigger',
         onTriggerClick: function() {
         // 获取当前已选择文件的智能表单项一级审批流程
         var store = this.up('grid').getStore();
         var code = "";
         for (var i = 0; i < store.count(); i++) {
         if (store.getAt(i).get("code_file_code") && store.getAt(i).get("code_file_code") != this.value) {
         code = store.getAt(i).get("code_file_code");
         break;
         }
         }
         //codeStore.load({params: {search_state: 1, step: step, code: code}});

         // 文件编码选择
         var grid = this.up('grid');

         codeStore.removeAll();
         var winCodeSelect = createFileCodeSelect(codeStore, params, step, grid, code);
         winCodeSelect.show();
         }
         }, renderer: function(value) {
         if (!value)
         return "点击选择文件编码";
         return value;
         }
         }*/, {
            text: '文件编码',
            dataIndex: 'code_file_code',
            emptyCellText: '选择文件编码',
            flex: 1,
            editor: {
                xtype: 'combobox',
                triggerCls: 'x-form-search-trigger',
                editable: true,
                displayField: 'code',
                valueField: 'code',
                triggerAction: 'all',
                forceSelection: true,
                selectOnFocus: true,
                lazyRender: true,
                store: codeStore,
                queryParam: 'search_code',
                minChars: 2,
                queryMode: 'remote',
                onTriggerClick: function() {
                    var store = code_file_store;
                    var code = "";
                    for (var i = 0; i < store.count(); i++) {
                        if (store.getAt(i).get("code_file_code") && store.getAt(i).get("code_file_code") != this.value) {
                            code = store.getAt(i).get("code_file_code");
                            break;
                        }
                    }
                    var winCodeSelect = createFileCodeSelect(codeStore, params, step, grid, code);
                    winCodeSelect.show();
                },
                listeners: {
                    change: function (obj, newValue, oldValue, e) {
                        if (newValue) {
                            var newest_ver = "";
                            var project_no = "";
                            var project_name = "";
                            var model_id = "";
                            var dev_model_id = "";
                            var description = "";
                            codeStore.each(function (r) {
                                if (r.get('code') == newValue) {
                                    newest_ver = r.get('ver');
                                    project_no = r.get('project_no');
                                    project_name = r.get('project_name');
                                    model_id = r.get('model_id');
                                    dev_model_id = r.get('dev_model_id');
                                    description = r.get('description');

                                    var sel = grid.getView().getSelectionModel().getSelection();
                                    var s = sel[0];
                                    s.set("newest_ver", newest_ver);
                                    s.set("project_no", project_no);
                                    s.set("project_name", project_name);
                                    s.set("model_id", model_id);
                                    s.set("dev_model_id", dev_model_id);
                                    s.set("description", description);
                                    if (Ext.getCmp('model_id')) {
                                        Ext.getCmp('model_id').setValue(model_id);
                                    }

                                    if (Ext.getCmp('dev_model_id')) {
                                        Ext.getCmp('dev_model_id').setValue(dev_model_id);
                                    }
                                }
                            });

                            var ver = "";
                            var model_id = "";
                            codeStore.each(function (r) {
                                if (r.get('code') == newValue) {
                                    model_id = r.get('model_id');
                                    ver = r.get('ver');
                                }
                            });
                            if (0) {
                                createIntelligenceForm(this.up('form'), newValue, [2, 2]);
                            }
                        }
                    }
                }
            }
        }, {
            text: '选择文件号',
            dataIndex: 'newest_ver',
            hidden: true,
            align: 'center',
            flex: .6,
            renderer: function (data, metadata, record, rowIndex, columnIndex, store) {
                metadata.style = "padding:0;";
                var resultStr = "<button  onclick='javscript:return false;' class='order_bit'>选择</button>";
                return "<div class='controlBtn'>" + resultStr + "</div>";
            }
        }, {
            text: '产品型号',
            dataIndex: 'project_no',
            flex: 1,
            editor: {
                xtype: 'combobox',
                editable: true,
                id: 'project_no',
                name: 'project_no',
                displayField: 'name',
                valueField: 'id',
                triggerAction: 'all',
                forceSelection: true,
                selectOnFocus:true,
                lazyRender: true,
                store: store,
                queryParam: 'q',
                minChars: 2,
                queryMode: 'local'
            },
            renderer : function(value, p, record) {
                if(record.data.project_name) {
                    return record.data.project_name;
                }
                return value;
            }
        }, {
            text: '描述',
            dataIndex: 'description',
            hidden: step == 'apply',
            flex: 1,
            editor: {
                allowBlank: false,
                xtype : 'textfield',
                maxLength : 150
            }
        }, {
            text: '现在版本',
            dataIndex: 'newest_ver',
            hidden: step == 'apply',
            flex: .5
        }, {
            text: '新文件自定义表单',
            dataIndex: 'model_id',
            hidden: true,
            flex: .5
        }, {
            text: '升版自定义表单',
            dataIndex: 'dev_model_id',
            hidden: true,
            flex: .5
        }, {
            text: '文件', dataIndex: 'code_file_file', flex: 2, editor: {
                xtype: 'triggerfield',
                editable: false,
                emptyText: '选择文件',
                triggerCls: 'x-form-search-trigger',
                onTriggerClick: function () {
//                        uploadStore.load({params: {search_archive: 0, search_del: 0}});

                    // 文件选择
//                        var uploadSelect = createUploadSelect(uploadStore, 'code_file_file_id', 'code_file_file', this.up('grid'));
//                        var winUpload = createWinUpload(uploadSelect);
//                        winUpload.show();
                    var fileSelect = lib.dcc.FileSelect({
                        store: uploadStore,
                        returnId: 'code_file_file_id',
                        returnValue: 'code_file_file',
                        grid: this.up('grid'),
                        userid: userid
                    });
                    fileSelect.show();
                }
            }, renderer: function (value) {
                if (!value)
                    return "点击选择文件";
                return value;
            }
        }
        ],
        listeners: {
            cellclick: function (obj, td, columnIndex, record, tr, rowIndex, e) {
                var btn = e.getTarget('.controlBtn');
                grid = this;
                if (btn) {
                    record = grid.getStore().getAt(rowIndex);
                    var store = code_file_store;
                    var code = "";
                    for (var i = 0; i < store.count(); i++) {
                        if (store.getAt(i).get("code_file_code") && store.getAt(i).get("code_file_code") != this.value) {
                            code = store.getAt(i).get("code_file_code");
                            break;
                        }
                    }
                    //codeStore.load({params: {search_state: 1, step: step, code: code}});

                    // 文件编码选择
//                    var grid = this.up('grid');

//                    store.removeAll();
                    var winCodeSelect = createFileCodeSelect(codeStore, params, step, grid, code);
                    winCodeSelect.show();
                }
            }
        }
    });

    return grid;
};