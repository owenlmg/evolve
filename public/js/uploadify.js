/*
 * 文件上传公共模块
 * 调用方法：
 *    var uploadify = lib.dcc.uploadify({grid: grid});
 *    uploadify.show();
 */
var lib = lib || {};
lib.dcc = lib.dcc || {};
(function(window, undefined) {

    lib.dcc.uploadify = function(opts) {
        return new uploadify(opts);
    };
    var uploadify = function(opts) {
        // 1 创建Grid
        var store = opts.store;
        var returnId = opts.returnId;
        var returnValue = opts.returnValue;
        var grid = opts.grid;
        var userid = opts.userid;
        lib.dcc.returnId = new Array();

        var treeStore = Ext.create('Ext.data.TreeStore', {
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
                }],
            autoLoad : false
        });

        var deptStore = Ext.create('Ext.data.TreeStore', {
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
                }],
                autoLoad : false
        });

        var categoryStore = Ext.create('Ext.data.Store', {
            model: 'codemaster',
            proxy: {
                type: 'ajax',
                reader: 'json',
                url: getRootPath() + '/public/dcc/type/getcodemaster/type/category'
            },
            autoLoad: true
        });

        this.form = createForm.call(this, store, treeStore, deptStore, categoryStore, userid, returnId, returnValue);
        this.win = createWin.call(this, this.form);
    };

    uploadify.prototype = {
        show: function() {
            this.win.show();
        }
    };

    // 查看详情
    function createForm(uploadStore, treeStore, deptStore, categoryStore, userid, returnId, returnValue) {
        var form = Ext.create('Ext.form.Panel', {
            bodyPadding: 5,
            border:0,
            width: 600,
            layout: 'form',
            id: 'fileForm',
            autoScroll: true,
            waitMsgTarget: true,
            fieldDefaults: {
                labelAlign: 'left',
                labelWidth: 85,
                margin: '0 10 0 0',
                msgTarget: 'side'
            },
            items: [{
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
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
                        }
                    ]}, {
                        region: 'center',
                        id: 'uploadPanel',
                        xtype: 'panel',
                        border : 0,
                        items: [{
                            html: '<input id="uploadFiles" type="file" name="uploadFiles" multiple="true" /><div id="fileQueue" class="fileQueue"></div>'
                        }],
                        listeners: {
                            afterrender : function() {
                            	$("#uploadFiles").uploadify({
                            		swf: getRootPath() + '/public/js/uploadify/scripts/uploadify.swf',
                            		uploader:getRootPath() + '/public/dcc/upload/multiupload',
                            		formData:{
                                		'employee_id' : userid
                            		},
                            		queueID:'fileQueue',
                            		buttonText:'选择文件',
                            		fileObjName : 'file',
                            		auto : true,
                            		removeCompleted: false,
                            		uploadLimit: 99,
                            		fileSizeLimit: '500MB',
                            		'onUploadStart' : function(file) {
                            			if(file.name.indexOf(',') >= 0) {
                            				Ext.Msg.alert('提示', '文件名中不能包含英文逗号:' + file.name);
                            				$("#uploadFiles").uploadify('cancel', file.id);
                            			}
                            		},
                            		'onUploadSuccess' : function(file, data, response) {
                            			if(data) {
                            				var json = Ext.JSON.decode(data);
                            				if(json.result) {
                            					lib.dcc.returnId.push(json.id);
                            				} else {
                            					$("#uploadFiles").uploadify('cancel', file.id);
                            					Ext.Msg.alert('提示', json.info + ':' + file.name);
                            				}
                            			}
                                    },
                                    'onUploadError' : function(file, errorCode, errorMsg, errorString) {
//                                         alert('文件 ' + file.name + ' 上传失败: ' + errorString);
                                    },
                            		'onQueueComplete': function(queueData) {
                                    	if (queueData.uploadsErrored) {
                                    		Ext.Msg.alert('提示', '文件上传错误');
                                    	} else {
//                                     		Ext.MessageBox.alert('提示', '文件上传成功');
//                                     		form.reset();
//                                             uploadStore.load();
//                                             win.hide();
                                    	}
                            		},
                            		'onFallback': function () { 
                            			alert("您未安装FLASH控件，无法上传！请安装FLASH控件后再刷新本页面。");
                            			window.open('http://get.adobe.com/cn/flashplayer/');
                            		}
                                });
                            }
                        }
                    }, {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    items: [{
                            xtype: 'checkbox',
                            checked: false,
                            id: 'private',
                            name: 'private',
                            readOnly : true,
                            flex: 2,
                            fieldLabel: '私有',
                            listeners: {
                                'change': function(obj, newValue, oldValue, eOpts) {
                                    Ext.getCmp('share_name').setVisible(!newValue);
                                    Ext.getCmp('share_dept').setVisible(!newValue);
                                    Ext.getCmp('share_time_begin').setVisible(!newValue);
                                    Ext.getCmp('share_time_end').setVisible(!newValue);
                                }
                            }
                        }, {
                            xtype: 'combobox',
                            fieldLabel: '文件类别',
                            id: 'category',
                            name: 'category',
                            emptyText: '无',
                            flex: 2,
                            editable: false,
                            forceSelection: true,
                            displayField: 'text',
                            valueField: 'id',
                            triggerAction: 'all',
                            lazyRender: true,
                            store: categoryStore,
                            queryMode: 'local'
                        }
                    ]
                }, {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    items: [Ext.create("Ext.ux.comboboxtree", {
                            id: 'share_name',
                            name: 'share_name',
                            hiddenName: 'employeeId',
                            flex: 2,
                            labelWidth: 85,
                            editable: false,
                            store: treeStore,
                            cascade: 'child', //级联方式:1.child子级联;2.parent,父级联,3,both全部级联
                            onlyLeaf: true, //是否只选择叶子节点
                            fieldLabel: '共享给个人',
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
                            flex: 2,
                            labelWidth: 85,
                            editable: false,
                            store: deptStore,
                            cascade: 'child', //级联方式:1.child子级联;2.parent,父级联,3,both全部级联
                            onlyLeaf: false, //是否只选择叶子节点
                            fieldLabel: '共享给部门',
                            displayField: 'text',
                            valueField: 'id',
                            rootId: '0',
                            rootText: 'DRP',
                            treeNodeParameter: ''
                        })
                    ]
                }, {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    items: [{
                            xtype: 'datefield',
                            format: 'Y-m-d',
                            id: 'share_time_begin',
                            name: 'share_time_begin',
                            flex: 2,
                            fieldLabel: '共享时间从',
                            value: new Date()
                        }, {
                            xtype: 'datefield',
                            format: 'Y-m-d',
                            id: 'share_time_end',
                            name: 'share_time_end',
                            flex: 2,
                            fieldLabel: '共享时间至'
                        }
                    ]
                }, {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    items: [{
                            xtype: 'textarea',
                            fieldLabel: '描述',
                            rows: 2,
                            flex: 2,
                            name: 'description'
                        }, {
                            xtype: 'textarea',
                            fieldLabel: '备注',
                            rows: 2,
                            flex: 2,
                            name: 'remark'
                        }
                    ]
                }, {
                    xtype: "panel",
                    border: false,
                    height: 10,
                    html: '<hr width="100%">'
                }, {
                    xtype: "panel",
                    border: false,
                    hidden: true,
                    height: 20,
                    html: '<div style="font-size:85%">说明一：私有文件大小限制500M以内，其他文件100M以内。</div>'
                }, {
                    xtype: "panel",
                    border: false,
                    height: 20,
                    html: '<div style="font-size:85%">说明一：目前支持以下文件类型在线浏览：office、txt、pdf。</div>'
                }, {
                    xtype: "panel",
                    border: false,
                    height: 20,
                    html: '<div style="font-size:85%">说明二：文件名中不能使用英文逗号“,”。</div>'
                }
            ],
            buttons: [{
                    text: '保存',
                    handler: function() {
                        var form = this.up('form').getForm();
                        var window = this.up('form').up('window');
                        if (form.isValid()) {
//                         	$('#uploadFiles').uploadify('upload','*');
                        	var isAdd = !Ext.getCmp('uploadPanel').isHidden();
                        	if(isAdd && lib.dcc.returnId.length == 0) {
                            	// 没有上传文件
                        		Ext.MessageBox.alert('错误', '没有上传文件');
                        		return false;
                        	}
                            form.submit({
                                url: getRootPath() + '/public/dcc/upload/save',
                                waitMsg: '提交中，请稍后...',
                                params: {uploadedFileIds : lib.dcc.returnId.join(',')},
                                success: function(form, action) {
                                    if (action.result.success) {
                                        var info = "";
                                        if (!action.result.convert) {
                                            info = "， 在线浏览文件转换失败";
                                        }
                                        Ext.MessageBox.alert('提示', action.result.info + info);
                                        if (action.result.result) {
                                            form.reset();
                                            if(isAdd) {
                                                $("#uploadFiles").uploadify('cancel');
                                            }
                                            uploadStore.load();
                                            window.close();
                                        }
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
                    text: '取消',
                    handler: function() {
                        this.up('form').getForm().reset();
                        if(!Ext.getCmp('uploadPanel').isHidden()) {
                            $("#uploadFiles").uploadify('cancel');
                        }
                        this.up('form').up('window').close();
                    }
                }]
        });
        return form;
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