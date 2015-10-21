/*
 * 文件查看公共模块
 * 调用方法：
 *    var fileView = lib.dcc.FileView({grid: grid});
 *    fileView.show();
 */
var lib = lib || {};
lib.dcc = lib.dcc || {};

(function(window, undefined) {

    lib.dcc.FileView = function(opts) {
        return new FileView(opts);
    };

    var FileView = function(opts) {
        // 1 加载文件数据

        // 2 加载自定义表单数据
        // 1 创建Form
        var grid = opts.grid;
        var source = opts.model;
        if(!source) source = "undefined";
        this.form = createForm.call(this, grid.data.id, source);
        this.form.getForm().reset();
        this.form.getForm().loadRecord(grid);
        // 2 改变Form
        // 2.1 文件区域
        // 2.1.1 根据id加载文件内容
        var data = getData(grid.data.id);
        // 2.1.2 清除现有文件
        /*var fileField = Ext.getCmp('fileField');
        fileField.removeAll();
        // 2.1.3 创建文件区域
        var k = 1;
        for (var i = 0; i < data.length; i++) {
            var label = data[i]['code'] ? '文件'+k++ : '';
            if(data.length === 1) {
                label = "文件";
            }
            var role = data[i]['exists'] && data[i]['role'];

            if(i === 0) {
                fileField.add(createFileViewField(label, data[i]['id'], data[i]['file_id'], data[i]['code'], data[i]['ver'], data[i]['file_name'], data[i]['file_path'], source, data[i]['state'], role));
            } else if(data[i]['code'] === data[i-1]['code']) {
                fileField.add(createFileViewNoCodeField(label, data[i]['id'], data[i]['file_id'], data[i]['code'], data[i]['ver'], data[i]['file_name'], data[i]['file_path'], source, data[i]['state'], role));
            } else {
                fileField.add(createFileViewField(label, data[i]['id'], data[i]['file_id'], data[i]['code'], data[i]['ver'], data[i]['file_name'], data[i]['file_path'], source, data[i]['state'], role));
            }
        }*/
        // 升版区域
        var baseinfo = Ext.getCmp("baseinfo");
        if (baseinfo !== undefined)
            baseinfo.removeAll();
        if (grid.data.ver > 1.0) {
            if (baseinfo !== undefined) {
                var reason = {
                    xtype: 'displayfield',
                    fieldLabel: '升级原因',
                    name: 'reason',
                    value: grid.data.reason
                };
                baseinfo.add(reason);
                baseinfo.show();
            }
        } else {
            if (baseinfo !== undefined) {
                baseinfo.removeAll();
                baseinfo.hide();
            }
        }

        // 清空自定义区域
        var ownerField = Ext.getCmp("reviewForm_ownerField");
        ownerField.hide();
        ownerField.removeAll();
        // 添加自定义区域
        var menu = "oa_doc_files_" + grid.get('id');
        createDisplay(ownerField, menu, null);

        // 清空审核区域
        var reviewField = Ext.getCmp("reviewField");
        reviewField.hide();
        reviewField.removeAll();

        // 添加审核区域
        reviewField.add(createRecordGrid('oa_doc_files', grid.data.id, false));
        reviewField.show();

        var component = opts.component;
        if(component !== null && component !== undefined) {
            if(opts.index) {
                this.form.insert(opts.index, component);
            } else {
                this.form.add(component);
            }
        }

        // 创建panel
        var title = opts.title;
        if(title === null || title === undefined) {
            title = '查看记录';
        }
        this.win = createWin.call(this, this.form, title);
    };

    function getData(id) {
        var data;
        Ext.Msg.wait('加载中，请稍后...', '提示');
        Ext.Ajax.request({
            url: getRootPath() + '/public/dcc/files/getfilesbyid',
            params: { id : id },
            method: 'POST',
            async : false,
            success: function(response, options) {
                data = Ext.JSON.decode(response.responseText);
                Ext.Msg.hide();
            },
            failure: function(response) {
                Ext.MessageBox.alert('提示', '加载失败');
                data = [];
            }
        });
        return data;
    }

    FileView.prototype = {
        show: function() {
            this.win.show();
        }
    };

    // 查看详情
    function createForm(id, source) {
        var form = new Ext.form.Panel({
            width: 790,
            bodyPadding: 5,
            layout: 'form',
            autoScroll: true,
            waitMsgTarget: true,
            fieldDefaults: {
                labelAlign: 'left',
                labelWidth: 85,
                msgTarget: 'side'
            },
            items: [{
                    xtype: 'fieldset',
                    title: '基本信息',
                    baseCls: 'x-fieldset',
                    width: 660,
                    items: [{
                            xtype: 'textfield',
                            hidden: true,
                            name: 'id'
                        }, {
                            id: 'fileField',
                            baseCls: 'x-fieldset-none',
                            xtype: 'fieldset',
                            items: [Ext.create('Ext.grid.Panel', {
                                store: Ext.create('Ext.data.Store', {
                                    model: Ext.define('record', {
                                        extend: 'Ext.data.Model',
                                        idProperty: 'file_id',
                                        fields: [
                                            {name: 'id', type: 'string'},
                                            {name: 'code', type: 'string'},
                                            {name: 'exists', type: 'int'},
                                            {name: 'file_id', type: 'string'},
                                            {name: 'file_name', type: 'string'},
                                            {name: 'file_path', type: 'string'},
                                            {name: 'project_no', type: 'string'},
                                            {name: 'project_name', type: 'string'},
                                            {name: 'description', type: 'string'},
                                            {name: 'role', type: 'string'},
                                            {name: 'state', type: 'string'},
                                            {name: 'ver', type: 'string'}
                                        ]
                                    }),
                                    proxy: {
                                        type: 'ajax',
                                        reader: 'json',
                                        url: getRootPath() + '/public/dcc/files/getfilesbyid?id=' + id
                                    },
                                    autoLoad: true
                                }),
                                border: true,
                                columnLines: true,
                                maximizable: true,
                                maximized: true,
                                columns: [
                                    {text: 'ID',hidden: true,dataIndex: 'id', width: 40},
                                    {text: '文件号', dataIndex: 'code', width: 100},
                                    {text: '产品型号', dataIndex: 'project_name', width: 120,renderer:showTitle},
                                    {text: '版本', dataIndex: 'ver', width: 40},
                                    {text: '描述', dataIndex: 'description', width: 200,renderer:showTitle},
                                    {text: '文件', dataIndex: 'file_name', width: 260, renderer : function(value, p, record) {
                                        if(!value) {
                                            return '';
                                        }
                                        var file_path = record.data.file_path;
                                        var state = record.data.state;
                                        var role = record.data.role;
                                        var file_id = record.data.file_id
                                        var icon = "";
                                        if (file_path && (state != 'Obsolete' || source == 'edit') && (role=='true' || source == 'edit')) {
                                            icon = '<img src="' + getRootPath() + '/public/images/icons/download.png" onclick="download(' + file_id + ', \'' + source + '\')" style="cursor:pointer;"></img>';
                                        } else {
                                            icon = '<img src="' + getRootPath() + '/public/images/icons/download_n.png">';
                                        }
                                        var fileType = value.substr(value.indexOf(".")+1);
                                        if (file_id && checkFileType(fileType) && (state != 'Obsolete' || source == 'edit')) {
                                            url = getRootPath() + "/public/dcc/online/?id=" + file_id;
                                            icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview.png" onclick="javascript:window.open(\'' + url + '\')" style="cursor:pointer;"></img>';
                                        } else {
                                            icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview-gray.png">'
                                        }
                                        return icon + value;
                                    }}
                                ]
                            })]
                        }, {
                            xtype: 'displayfield',
                            fieldLabel: '文件描述',
                            hidden : true,
                            name: 'description'
                        }, {
                            xtype: 'displayfield',
                            fieldLabel: '备注',
                            name: 'remark'
                        }, {
                            xtype: 'displayfield',
                            fieldLabel: '关键字',
                            name: 'tag'
                        }, {
                            id: 'baseinfo',
                            baseCls: 'x-fieldset-none',
                            xtype: 'fieldset',
                            items: []
                        }]
                }, {
                    id: 'reviewForm_ownerField',
                    xtype: 'fieldset',
                    title: '自定义信息',
                    width: 660,
                    hidden: true,
                    items: []
                }, {
                    id: 'reviewField',
                    xtype: 'fieldset',
                    width: 660,
                    collapsed: true,
                    collapsible: true,
                    title: '审核历史',
                    hidden: true,
                    items: [],
                    listeners: {
                        expand: function(f, o) {
                            var store = Ext.getCmp('reviewField').down('grid').getStore();
                            if (store.getTotalCount() === 0) {
                                store.load({
                                    params: {
                                        table: 'oa_doc_files',
                                        id: this.up('form').getRecord().get('id')
                                    }
                                });
                            }
                        }
                    }
                }]
        });
        return form;
    };

    function createWin(form, title) {
        var win = new Ext.Window({
            xtype: "window",
            height: 460,
            title: title,
            maximizable: true,
            layout: 'fit',
            closable: true,
            items: [form]
        });
        return win;
    }
    ;
})(window);


