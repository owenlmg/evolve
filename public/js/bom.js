Ext.define('fa', {
    extend: 'Ext.data.Model',
    idProperty: 'sid',
    fields: [{ name: "sid" },
             { name: "id" },
             { name: "nid" },
             { name: "recordkey" },
             { name: "code" },
             { name: "ver" },
             { name: "qty" },
             { name: "type" },
             { name: "remark" },
             { name: "project_no" },
             { name: "bom_file" },
             { name: "file_ver" },
             { name: "file_desc" },
             { name: "bom_file_view" },
             { name: "project_no_name" },
             { name: "state" },
             { name: "name" },
             { name: "description" },
             { name: "archive_time", type: 'date', dateFormat: 'timestamp' },
             { name: "type_name" },
             { name: "description_head" },
             { name: "upd_type" },
             { name: "upd_reason" },
             { name: "reason_type" },
             { name: "reason_type_name" },
             { name: "remark" },
             { name: "remark_head" }
            ]
});

Ext.define('son', {
    extend: 'Ext.data.Model',
    idProperty: 'sid',
    fields: [{ name: "sid" },
             { name: "id" },
             { name: "nid" },
             { name: "pid" },
             { name: "recordkey" },
             { name: "code" },
             { name: "description" },
             { name: "name" },
             { name: "qty" },
             { name: "partposition" },
             { name: "replace" },
             { name: "remark" },
             { name: "state" }
            ]
});

Ext.define('materiel', {
    extend: 'Ext.data.Model',
    idProperty: 'sid',
    fields: [{ name: "id" },
             { name: "sid" },
             { name: "code" },
             { name: "type" },
             { name: "description" },
             { name: "remark1" },
             { name: "remark" },
             { name: "project_no" },
             { name: "bom_file" },
             { name: "ver" },
             { name: "unit" },
             { name: "state" },
             { name: "manufacturers" },
             { name: "supply1" },
             { name: "supply2" },
             { name: "mpq" },
             { name: "moq" },
             { name: "tod" },
             { name: "supply_code1" },
             { name: "supply_cname1" },
             { name: "supply_ename1" },
             { name: "supply_code2" },
             { name: "supply_cname2" },
             { name: "supply_ename2" },
             { name: "type_name" },
             { name: "unit_name" },
             { name: "creater" },
             { name: "create_time", type: 'date', dateFormat: 'timestamp' },
             { name: "archive_time", type: 'date', dateFormat: 'timestamp' },
             { name: "step_name" },
             { name: "review_state" },
             { name: "mytype" },
             { name: "auto" },
             { name: "data_file" },
             { name: "data_file_path" },
             { name: "data_file_id" },
             { name: "first_report" },
             { name: "first_report_path" },
             { name: "first_report_id" },
             { name: "tsr" },
             { name: "tsr_path" },
             { name: "tsr_id" },
             { name: "record"},
             { name: "name"},
             { name: "qty" },
             { name: "example"},
             { name: "replace"},
             { name: "partposition"}
            ]
});

Ext.define('project', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"},
        {name: "name"}]
});

var loadBom = function(nid, type) {
	var bomForm = lib.bom.bomForm({grid: Ext.getCmp('bomGrid'), type: type, nid: nid, step: 'view'});
    bomForm.show();
}

var viewBom = function(recordkey) {
	Ext.create('Ext.window.Window', {
        title: 'BOM',
        maximized: true,
        maximizable: false,
        layout: 'fit',
        html: "<iframe src='" + getRootPath() + "/public/product/bomview?recordkey=" + recordkey + "' frameborder=0 width=100% height=100%></iframe>"
    }).show();
};

var viewPrice = function(recordkey) {
    Ext.create('Ext.window.Window', {
        title: 'BOM',
        maximized: true,
        maximizable: false,
        layout: 'fit',
        html: "<iframe src='" + getRootPath() + "/public/product/bompricedetail?recordkey=" + recordkey + "' frameborder=0 width=100% height=100%></iframe>"
    }).show();
};

var viewUpdDetail = function(left_code, right_code, left_ver, right_ver) {
	Ext.create('Ext.window.Window', {
        title: 'BOM升版状况',
        maximized: true,
        maximizable: false,
        layout: 'fit',
        html: "<iframe src='" + getRootPath() + "/public/product/upddetail?left_code=" + left_code + "&right_code=" + right_code + "&left_ver=" + left_ver + "&right_ver=" + right_ver + "' frameborder=0 width=100% height=100%></iframe>"
    }).show();
};

/**
 * BOM创建及查看公用模块
 * 调用方法：
 *    var bomForm = lib.bom.bomForm({grid: grid});
 *    bomForm.show();
 */
var lib = lib || {};
lib.bom = lib.bom || {};

(function(window, undefined) {

	lib.bom.bomForm = function(opts) {
        return new bomForm(opts);
    };

    var bomForm = function(opts) {
        // 1 创建Form
        var type = opts.type;
    	lib.bom.grid = opts.grid;
        lib.bom.type = opts.type;
        lib.bom.nid = opts.nid;
        recordkey = opts.recordkey;
        lib.bom.step = opts.step;
        lib.bom.count = 0;
        lib.bom.firstFlg = 1;
        lib.bom.firstSonFlg = 1;
        lib.bom.win = opts.win;
        lib.bom.replace_flg = opts.replace_flg;
        lib.bom.replace = opts.replace;
        lib.bom.replaced = opts.replaced;
        if(lib.bom.replace_flg && (!lib.bom.replace || !lib.bom.replaced)) {
        	alert("主替代料批量替换需要先选被替换物料和替换物料");
        	return false;
        }
        if(type == 'new') {
        	lib.bom.sonStore = Ext.create('Ext.data.Store', {
                model: 'son',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/newbom/getson/nid/' + lib.bom.nid
                },
                autoLoad: false
            });
        	lib.bom.faStore = Ext.create('Ext.data.Store', {
                model: 'fa',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/newbom/getfa/nid/' + lib.bom.nid
                },
                autoLoad: false
            });
        	lib.bom.sonStore.load({
        		callback:function() {
        		    if(lib.bom.firstFlg) {
        		        lib.bom.faStore.load();
        		        lib.bom.firstFlg = 0;
        		    }
        	    }
        	});

        	lib.bom.rightStore = Ext.create('Ext.data.Store', {
                model: 'son'
        	});

        	selectfaStore = createFaStore(type);
        	selectsonStore = createSonStore(type);
        } else if(type == 'DEV' || type == 'ECO') {
        	lib.bom.sonStore = Ext.create('Ext.data.Store', {
                model: 'son',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/updbom/getson/nid/' + lib.bom.nid
                },
                autoLoad: false
            });
        	lib.bom.faStore = Ext.create('Ext.data.Store', {
                model: 'fa',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/updbom/getfa/nid/' + lib.bom.nid
                },
                autoLoad: false
            });
        	lib.bom.sonStore.load({
        		callback:function() {
        		    if(lib.bom.firstFlg) {
        		        lib.bom.faStore.load();
        		        lib.bom.firstFlg = 0;
        		    }
        	    }
        	});

        	lib.bom.rightStore = Ext.create('Ext.data.Store', {
                model: 'son'
        	});

        	selectfaStore = createFaStore(type);
        	selectsonStore = createSonStore(type);
        } else if(type == 'edit') {
        	lib.bom.sonStore = Ext.create('Ext.data.Store', {
                model: 'son',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/bom/getson/recordkey/' + recordkey
                },
                autoLoad: false
            });
        	lib.bom.faStore = Ext.create('Ext.data.Store', {
                model: 'fa',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/product/bom/getfa/recordkey/' + recordkey
                },
                autoLoad: false
            });
        	lib.bom.sonStore.load({
        		callback:function() {
        		    if(lib.bom.firstFlg) {
        		        lib.bom.faStore.load();
        		        lib.bom.firstFlg = 0;
        		    }
        	    }
        	});

        	lib.bom.rightStore = Ext.create('Ext.data.Store', {
                model: 'son'
        	});

        	selectfaStore = createFaStore(type);
        	selectsonStore = createSonStore(type);
        } else if(type == 'eco') {
        	lib.bom.faStore = createFaStore(type);
        	selectfaStore = createFaStore(type);
        	lib.bom.sonStore = createSonStore(type);
        	selectsonStore = createSonStore(type);
        } else {
        	Ext.MessageBox.alert('错误', '参数不正确！');
        	return false;
        }

    	lib.bom.existSonStore = Ext.create('Ext.data.Store', {
            model: 'son',
            proxy: {
                type: 'ajax',
                reader: 'json',
                url: getRootPath() + '/public/product/bom/getexistson/'
            },
            autoLoad: false
        });
        lib.bom.left = createLeft(selectfaStore);
        lib.bom.projectStore = createProjectInternalStore(type)
        lib.bom.filecodeStore = createFileCodeStore()
        var bomId = "1"; //TODO
        this.rightUp = createRightUp(bomId);
        this.rightDown = createRightDown(selectsonStore);
        //this.left.getSelectionModel().select(0);

        // 2 改变Form
        var component = opts.component;
        if(component !== null && component !== undefined) {
            if(opts.index) {
                this.faForm.insert(opts.index, component);
            } else {
                this.faForm.add(component);
            }
        }

        // 创建panel
        title = '查看记录';
        if(type == 'new') {
        	title = '创建新BOM';
        } else if(type == 'DEV' || type == 'ECO') {
        	title = 'BOM升版';
        }
        this.win = createWin.call(this, lib.bom.left, this.rightUp, this.rightDown, title);
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

    bomForm.prototype = {
        show: function() {
    	    if(lib.bom.step == 'view') {
		        // 隐藏所有按钮
		        Ext.getCmp('leftAddBtn').setDisabled(true);
		        Ext.getCmp('leftDelBtn').setDisabled(true);
		        Ext.getCmp('rightDownAddBtn').setDisabled(true);
		        Ext.getCmp('copyFrom').setDisabled(true);
		        Ext.getCmp('rightDownDelBtn').setDisabled(true);
		        Ext.getCmp('save').setDisabled(true);
		        Ext.getCmp('importBtn').setDisabled(true);
    	    } else if(lib.bom.step == 'edit' && lib.bom.type == 'edit') {
		        // 隐藏上级BOM的按钮
		        Ext.getCmp('leftAddBtn').setDisabled(true);
		        Ext.getCmp('leftDelBtn').setDisabled(true);
		        Ext.getCmp('rightDownAddBtn').setDisabled(false);
		        Ext.getCmp('copyFrom').setDisabled(false);
		        Ext.getCmp('rightDownDelBtn').setDisabled(false);
		        Ext.getCmp('save').setDisabled(false);
		        Ext.getCmp('importBtn').setDisabled(true);
    	    } else {
		        // 显示所有按钮
		        Ext.getCmp('leftAddBtn').setDisabled(false);
		        Ext.getCmp('leftDelBtn').setDisabled(false);
		        Ext.getCmp('rightDownAddBtn').setDisabled(false);
		        Ext.getCmp('copyFrom').setDisabled(false);
		        Ext.getCmp('rightDownDelBtn').setDisabled(false);
		        Ext.getCmp('save').setDisabled(false);
		        Ext.getCmp('importBtn').setDisabled(false);
    	    }

            if(lib.bom.win) {
            	Ext.getCmp('reviewBtn').show();
            }
            this.win.show();
        }
    };

    /**
     * 创建Store
     * @param type 类型：new dev eco
     */
    function createFaStore(type) {
    	var faStore = Ext.create('Ext.data.Store', {
    	    model: 'materiel',
    	    proxy: {
    	        type: 'ajax',
    	        reader: 'json',
    	        url: getRootPath() + '/public/product/bom/getmateriel?model=fa&type=' + type
    	    },
    	    autoLoad: false
    	});
    	return faStore;
    }

    /**
     * 创建Store
     */
    function createSonStore(type) {
    	var sonStore = Ext.create('Ext.data.Store', {
    	    model: 'materiel',
    	    proxy: {
    	        type: 'ajax',
    	        reader: 'json',
    	        url: getRootPath() + '/public/product/bom/getmateriel?model=son&type='+type
    	    },
    	    autoLoad: false
    	});
    	return sonStore;
    }

    /**
     * 创建产品型号Store
     * @param type 类型：new dev eco
     */
    function createProjectInternalStore(type) {
    	// 产品系列数据源
        var projectStore = Ext.create('Ext.data.Store', {
            model: 'project',
            proxy: {
                type: 'ajax',
                reader: 'json',
                url: getRootPath() + '/public/dcc/code/getproject'
            },
            autoLoad: true
        });
    	return projectStore;
    }
    /**
     * 创建文件Store
     */
    function createFileCodeStore() {
    	// 产品系列数据源
    	var filecodeStore = Ext.create('Ext.data.Store', {
    	    model: 'code',
    	    pageSize: 15,
    	    proxy: {
	        type: 'ajax',
	        reader: 'json',
    	        url: getRootPath() + '/public/product/bom/getfilecode'
    	    },
    	    autoLoad: false
    	});
    	return filecodeStore;
    }

    function createLeft(selectfaStore) {
    	var rowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    	    clicksToEdit: 2
    	});
    	if(lib.bom.faStore == null || lib.bom.faStore == undefined) {
    		lib.bom.faStore = Ext.create('Ext.data.Store', {
        	    model: 'fa'
        	});
    	}
        var form = new Ext.grid.Panel({
        	store: lib.bom.faStore,
            border: true,
            bodyPadding: 2,
            title: '上级物料',
            region: 'west',
            border:0,
            height: 380,
            modal: true,
            closeAction: 'hide',
            layout: 'fit',
        	width: 200,
            plugins: rowEditing,
            viewConfig: {
                stripeRows: false
            },
            tbar: [{
                text: '添加',
                id : 'leftAddBtn',
                scope: this,
                handler: function(){
	                var winCodeSelect = createCodeSelect(selectfaStore, null, null, null, lib.bom.faStore);
	                winCodeSelect.show();
                }
            }, {
                text: '删除',
                id: 'leftDelBtn',
                scope: this,
                handler: function(){
	            	var selection = form.getSelectionModel().getSelection();
	                if (selection.length > 0){
	                	lib.bom.faStore.remove(selection);
	                	if(lib.bom.faStore.count() == 0) {
	                		Ext.getCmp('rightDownAddBtn').setDisabled(true);
	                		Ext.getCmp('copyFrom').setDisabled(true);
                		    Ext.getCmp('rightDownDelBtn').setDisabled(true);
                		    Ext.getCmp('remark_up').setDisabled(true);
                		    Ext.getCmp('project_no').setDisabled(true);
                		    Ext.getCmp('bom_file').setDisabled(true);
                		    Ext.getCmp('save').setDisabled(true);
                		    lib.bom.rightStore.removeAll();
	                	}
	                } else {
	                    Ext.MessageBox.alert('错误', '没有选择删除对象！');
	                }
	            }
            }, {
                text: '导入',
                id  : 'importBtn',
                scope: this,
                handler: function(){
                	importBomWin.show();
                }
            }],
            columns: [{
                    text: 'BOM号',
                    dataIndex: 'code',
                    sortable: false,
                    width: 190,
                    renderer: function(value, p, record) {
		            	if(value && record.get('ver') > 1.0 && lib.bom.type != 'new') {
		            		return value + " <b>V" + record.get('ver') + "</b>";
		            	} else {
		            		return value;
		            	}
		            }
                }
            ],
            listeners: {
                selectionchange: function(model, records) {
                    if (records[0]) {
            		    record= records[0];
            		    lib.bom.recordkey = record.get('recordkey');
            		    lib.bom.selectedCode = record.get('code');
            		    lib.bom.selectedVer = record.get('ver');
            		    if(lib.bom.type != 'new' && lib.bom.recordkey && lib.bom.selectedCode && lib.bom.selectedVer > 1.0) {
                        	Ext.getCmp('updDetailBtn').show();
                        }
            		    if(record.data.remark) {
            		    	Ext.getCmp('remark_up').setValue(record.data.remark);
            		    } else {
            		    	Ext.getCmp('remark_up').setValue("");
            		    }
            		    if(record.data.project_no) {
            		    	Ext.getCmp('project_no').setValue(record.data.project_no);
            		    } else {
            		    	Ext.getCmp('project_no').setValue(0);
            		    }
            		    if(record.data.bom_file) {
            		    	Ext.getCmp('bom_file').setValue(record.data.bom_file);
            		    } else {
            		    	Ext.getCmp('bom_file').setValue("");
            		    }
            		    lib.bom.leftRecord = records[0];
            		    var rightUpForm = Ext.getCmp("rightUpForm").getForm();
            		    rightUpForm.loadRecord(lib.bom.leftRecord);

            		    var pid = record.data.id;
            		    var faRecord=new Array();
            		    lib.bom.faStore.each(function(r) {
            		    	var d = {
            		    		'nid' : lib.bom.nid,
            		    		'id' : r.data.id,
            		    	    'remark' : r.data.remark,
            		    	    'project_no' : r.data.project_no,
            		    	    'bom_file' : r.data.bom_file
            		    	};
            		    	faRecord.push(d);
            		    });

            		    var sonRecord=new Array();
            		    lib.bom.rightStore.removeAll();
            		    lib.bom.sonStore.each(function(r) {
            		    	var d = {
                		    	'nid'          : lib.bom.nid,
            		    		'id'           : r.data.id,
            		    		'pid'          : r.data.pid,
            		    	    'remark'       : r.data.remark,
            		    	    'qty'          : r.data.qty,
            		    	    'partposition' : r.data.partposition,
            		    	    'replace' 	   : r.data.replace
            		    	};
            		    	sonRecord.push(d);
            		    	if(r.data.pid == pid) {
            		    		lib.bom.rightStore.add(r);
            		    	}
            		    });

            		    // 加载子bom
            		    var code = records[0].get('code');
            		    var pid = records[0].get('id');
            		    if(lib.bom.type == 'DEV' || lib.bom.type == 'ECO' || lib.bom.type == 'new') {
            		    	var noLoad = false;
            		    	lib.bom.sonStore.each(function(r) {
            		    		if(r.get('pid') == pid) {
            		    			noLoad = true;
            		    			return false;
            		    		}
            		    	})
            		    	if(!noLoad) {
	            		    	lib.bom.existSonStore.load({
	            		    		params:{code: code},
	            		    		callback:function() {
	        		        		    lib.bom.existSonStore.each(function(record) {
	        		        		    	lib.bom.sonStore.add(record);
	        		        		    	lib.bom.rightStore.add(record);
	        		        		    });
	        		        	    }
	            		    	});
            		    	}
            		    }
            		    // 保存
            		    if(lib.bom.count > 0 && lib.bom.step != 'view') {
	             		    Ext.Ajax.request({
	                            url: getRootPath() + '/public/product/bom/autosave',
	                            params: {nid: lib.bom.nid, type: lib.bom.type, fa: Ext.encode(faRecord), son: Ext.encode(sonRecord)},
	                            method: 'POST',
	                            success: function(response, options) {
	                                var data = Ext.JSON.decode(response.responseText);
	                            },
	                            failure: function(form, action) {
	                                Ext.MessageBox.alert('错误', '数据保存失败,'+action.result.info);
	                            }
	                        });
            		    }
                    }
                }
            }
        });

        lib.bom.faStore.on("datachanged", function() {
    		if(lib.bom.faStore.count() > 0) {
    		    form.getSelectionModel().select(0);
    		    lib.bom.count++;

    		    if(lib.bom.step == 'view') {
    		        // 隐藏所有按钮
    		    	Ext.getCmp('rightDownAddBtn').setDisabled(true);
    		    	Ext.getCmp('copyFrom').setDisabled(true);
        		    Ext.getCmp('rightDownDelBtn').setDisabled(true);
        		    Ext.getCmp('remark_up').setDisabled(true);
        		    Ext.getCmp('project_no').setDisabled(true);
        		    Ext.getCmp('bom_file').setDisabled(true);
        		    Ext.getCmp('bom_file').hide(true);
        		    Ext.getCmp('bom_file_view').show(true);
        		    Ext.getCmp('importBtn').setDisabled(true);
        	    } else if(lib.bom.step == 'edit' && lib.bom.type == 'edit') {
    		        // 隐藏上级BOM的按钮
    		        Ext.getCmp('leftAddBtn').setDisabled(true);
    		        Ext.getCmp('leftDelBtn').setDisabled(true);
    		        Ext.getCmp('rightDownAddBtn').setDisabled(false);
    		        Ext.getCmp('copyFrom').setDisabled(false);
    		        Ext.getCmp('rightDownDelBtn').setDisabled(false);
    		        Ext.getCmp('save').setDisabled(false);
    		        Ext.getCmp('importBtn').setDisabled(true);
        		    Ext.getCmp('remark_up').setDisabled(false);
        		    Ext.getCmp('project_no').setDisabled(false);
        		    Ext.getCmp('bom_file').setDisabled(false);
        		    Ext.getCmp('bom_file').show(true);
        		    Ext.getCmp('bom_file_view').hide(true);
        	    } else {
    		        // 显示所有按钮
        	    	Ext.getCmp('rightDownAddBtn').setDisabled(false);
        	    	Ext.getCmp('copyFrom').setDisabled(false);
        		    Ext.getCmp('rightDownDelBtn').setDisabled(false);
        		    Ext.getCmp('remark_up').setDisabled(false);
        		    Ext.getCmp('project_no').setDisabled(false);
        		    Ext.getCmp('bom_file').setDisabled(false);
        		    Ext.getCmp('bom_file').show(true);
        		    Ext.getCmp('bom_file_view').hide(true);
        		    Ext.getCmp('importBtn').setDisabled(false);
        	    }

    		    records = form.getSelectionModel().getSelection();
    		    lib.bom.leftRecord = records[0];
    		    var rightUpForm = Ext.getCmp("rightUpForm").getForm();
    		    rightUpForm.loadRecord(lib.bom.leftRecord);
    		}
    	});
        return form;
    };

    function createRightUp(bomId) {
    	var form = new Ext.form.Panel({
	        bodyPadding: 2,
	        border: 0,
	        id: 'rightUpForm',
	        layout:'form',
            //title: '物料属性',
	        waitMsgTarget: true,

	        fieldDefaults: {
	            labelAlign: 'right',
	            labelWidth: 95,
	            msgTarget: 'side'
	        },
	        items: [{
	            layout: 'column',
	            border: 0,
	            items: [{
	                xtype: 'textfield',
	                name: 'id',
	                hidden: true
	            }, {
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'displayfield',
		                fieldLabel: '物料号',
		                name: 'code'
		            }]
	            }, {
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'displayfield',
		                fieldLabel: '物料名称',
		                name: 'name'
		            }]
	            }]
	        }, {
	            layout: 'column',
	            border: 0,
	            items: [{
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'displayfield',
		                fieldLabel: '类别',
		                name: 'type_name'
		            }]
	            }, {
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'displayfield',
		                fieldLabel: '物料描述',
		                name: 'description'
		            }]
	            }]
	        }, {
	            layout: 'column',
	            border: 0,
	            items: [{
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'displayfield',
		                fieldLabel: '状态',
		                name: 'state',
		                renderer: function(value) {
		                	if(value) {
		                		return "<b>" + value + "</b>";
		                	}
		                }
		            }]
	            }, {
	                columnWidth: .4,
	                border: 0,
	                layout: 'form',
	                items: [{
	                    xtype: 'combobox',
	                    editable: true,
		                disabled: true,
	                    fieldLabel: '产品型号',
	                    flex: 2,
	                    id: 'project_no',
	                    name: 'project_no',
	                    displayField: 'name',
	                    valueField: 'id',
	                    triggerAction: 'all',
	                    forceSelection: true,
	                    selectOnFocus:true,
	                    emptyText: '无',
	                    lazyRender: true,
	                    store: lib.bom.projectStore,
				        queryParam: 'q',
				        minChars: 2,
				        queryMode: 'remote',
		                listeners: {
	                	    select: function() {
	                	        var thisVal = Ext.getCmp('project_no').getValue();
	                	        if(thisVal) {
		                	        lib.bom.faStore.each(function(e) {
		                	        	if(e.data.id == Ext.getCmp("rightUpForm").getForm().findField('id').getValue()) {
		                	        		e.data['project_no'] = thisVal;
		                	        	}
		                	        });
	                	        }
	                        }
	                    }
				    }]
	            }]
	        }, {
	            layout: 'column',
	            border: 0,
	            items: [{
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
	                	xtype: 'triggerfield',
	                    fieldLabel: '关联文件',
	                    editable: true,
		                disabled: true,
		                enableKeyEvents: true,
	                    id: 'bom_file',
	                    triggerCls: 'x-form-search-trigger',
	                    onTriggerClick: function() {
	                        var params = new Array();
	                        params["code"] = "bom_file";
	                        var grid = this.up('grid');
	                        var cb = function() {
	                        	var thisVal = Ext.getCmp('bom_file').getValue();
	                        	if(thisVal) {
		                	        lib.bom.faStore.each(function(e) {
		                	        	if(e.data.id == Ext.getCmp("rightUpForm").getForm().findField('id').getValue()) {
		                	        		e.data['bom_file'] = thisVal;
		                	        	}
		                	        });
	                	        }
	                        }

	                        var winCodeSelect = createFileCodeSelect(lib.bom.filecodeStore, params, 'dev', null, null, true, cb);
	                        winCodeSelect.show();
	                    },
		                listeners: {
	                	    blur: function() {
	                	        var thisVal = Ext.getCmp('bom_file').getValue();
	                	        if(thisVal) {
		                	        lib.bom.faStore.each(function(e) {
		                	        	if(e.data.id == Ext.getCmp("rightUpForm").getForm().findField('id').getValue()) {
		                	        		e.data['bom_file'] = thisVal;
		                	        	}
		                	        });
	                	        }
	                        },
	                        keyup: function() {
	                        	var thisVal = Ext.getCmp('bom_file').getValue();
	                        	if(thisVal) {
	                        		thisVal = thisVal.toUpperCase();
	                        		Ext.getCmp('bom_file').setValue(thisVal);
	                        		lib.bom.faStore.each(function(e) {
		                	        	if(e.data.id == Ext.getCmp("rightUpForm").getForm().findField('id').getValue()) {
		                	        		e.data['bom_file'] = thisVal;
		                	        	}
		                	        });
	                        	}
	                        }
	                    }
				    }]
	            }, {
	                columnWidth: .5,
	                border: 0,
	                layout: 'form',
	                items: [{
	                	xtype: 'textarea',
	                    fieldLabel: '关联文件',
	                    hidden: true,
	                    readOnly: true,
	                    id: 'bom_file_view',
	                    name: 'bom_file_view',
		                rows: 2,
		                cols: 50,
		                border: false
				    }]
	            }, {
	                columnWidth: .4,
	                border: 0,
	                layout: 'form',
	                items: [{
		                xtype: 'textarea',
		                rows: 2,
		                cols: 50,
		                disabled: true,
		                fieldLabel: '备注',
		                id: 'remark_up',
		                name: 'remark_up',
		                listeners: {
	                	    blur: function() {
	                	        var thisVal = Ext.getCmp('remark_up').getValue();
	                	        if(thisVal) {
		                	        lib.bom.faStore.each(function(e) {
		                	        	if(e.data.id == Ext.getCmp("rightUpForm").getForm().findField('id').getValue()) {
		                	        		e.data['remark'] = thisVal;
		                	        	}
		                	        });
	                	        }
	                        }
	                    }
	                }]
	            }]
	        }
	    ]
	    });
    	return form;
    };

    function createRightDown(selectsonStore) {
    	var rowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    	    clicksToEdit: 1
    	});
    	if(lib.bom.sonStore == null || lib.bom.sonStore == undefined) {
    		lib.bom.sonStore = Ext.create('Ext.data.Store', {
        	    model: 'son'
        	});
    	}
    	if(lib.bom.rightStore == null || lib.bom.rightStore == undefined) {
    		lib.bom.rightStore = Ext.create('Ext.data.Store', {
        	    model: 'son'
        	});
    	}
        var form = new Ext.grid.Panel({
        	store: lib.bom.rightStore,
        	id: 'rightDownForm',
            autoWidth: true,
            border: 0,
            bodyPadding: 2,
            //title: '下级物料',
            region: 'center',
            height: 380,
            modal: true,
            closeAction: 'hide',
            layout: 'fit',
            plugins: rowEditing,
            viewConfig: {
                stripeRows: false,
                listeners: {
                    add: function(node, data, dropRec, dropPosition) {
                        var store = this.getStore();
                        for (i = 0; i < store.getCount(); i++)
                        {
                            store.getAt(i).set('index', i + 1);//model类的set,写入编号
                        }
                    }
                }
            },
            tbar: [{
                text: '添加',
                scope: this,
                id: 'rightDownAddBtn',
                disabled: true,
                handler: function(){
            	    var params = new Array();
            	    params['pid'] = lib.bom.leftRecord.data.id;
	                var winCodeSelect = createCodeSelect(selectsonStore, params, null, null, lib.bom.rightStore, null, lib.bom.sonStore);
	                winCodeSelect.show();
	            }
            }, {
                text: '复制从',
                id  : 'copyFrom',
                disabled: true,
                scope: this,
                handler: function(){
            	    var params = new Array();
            	    params['pid'] = lib.bom.leftRecord.data.id;
                	var copyfaStore = createFaStore('DEV');
                	var cb = function(faid, pid) {
                		Ext.Msg.wait('加载中，请稍后...', '提示');
                		Ext.Ajax.request({
                            url: getRootPath() + '/public/product/bom/getcopyson',
                            params: {faid: faid, pid: pid},
                            method: 'POST',
                            success: function(response, options) {
                            	var selection = Ext.JSON.decode(response.responseText);
                            	for(i = 0; i < selection.length;i++) {
                        			var flg = false;
                        			lib.bom.rightStore.each(function(e) {
                            			if(e.get("code") == selection[i].code) {
                            				flg = true;
                            			}
                            		});
                        			if(!flg) {
                        				if(pid) {
                        				    selection[i].pid = pid;
                        				}
                        				var selectionModel = Ext.create('son', selection[i]);
//                        				selection[i].sid = "" + selection[i].id + pid;
                        				selectionModel.data.sid = "" + selectionModel.data.id + pid;
                        				lib.bom.rightStore.insert(0, selectionModel);
                        		    	lib.bom.sonStore.insert(0, selectionModel);
                        			}
                        		}
                            	Ext.Msg.hide();
                            },
                            failure: function(form, action) {
                                Ext.MessageBox.alert('错误', '数据加载失败,'+action.result.info);
                            }
                        });
                	}
                	var winCodeSelect = createCodeSelect(copyfaStore, null, null, null, null, null, null, cb, lib.bom.leftRecord.data.id);
	                winCodeSelect.show();
                }
            }, {
                text: '删除',
                id: 'rightDownDelBtn',
                disabled: true,
                scope: this,
                handler: function(){
	            	var selection = form.getSelectionModel().getSelection();
	                if (selection.length > 0){
	                	lib.bom.rightStore.remove(selection);
	                	lib.bom.sonStore.remove(selection);
	                	/*for(var i = 0; i < selection.length; i++) {
	                		var sid = "" + selection[i].data.id + lib.bom.leftRecord.data.id;
	                		for(var j = 0; j < lib.bom.sonStore.data.length; j++) {
	                			if(lib.bom.sonStore.data.items[j].data.sid == sid) {
	                				lib.bom.sonStore.remove(lib.bom.sonStore.data.items[j]);
	                			}
	                		}
	                	}*/
	                } else {
	                    Ext.MessageBox.alert('错误', '没有选择删除对象！');
	                }
	            }
            }, {
                text: '保存',
                id: 'save',
                formBind: true,
                handler: function(){
            	    var window = Ext.getCmp('bomWin');
            	    var faRecord=new Array();
	    		    lib.bom.faStore.each(function(record) {
	    		    	var d = {
	    		    		'id' : record.data.id,
	    		    	    'remark' : record.data.remark,
	    		    	    'project_no' : record.data.project_no,
	    		    	    'bom_file' : record.data.bom_file
	    		    	};
	    		    	faRecord.push(d);
	    		    });

	    		    var sonRecord=new Array();
	    		    lib.bom.sonStore.each(function(record) {
	    		    	var d = {
	    		    		'id'           : record.data.id,
	    		    		'pid'          : record.data.pid,
	    		    	    'remark'       : record.data.remark,
	    		    	    'qty'          : record.data.qty,
	    		    	    'partposition' : record.data.partposition,
	    		    	    'replace' 	   : record.data.replace
	    		    	};
	    		    	sonRecord.push(d);
	    		    });
	    		    if(lib.bom.type == 'edit' && sonRecord.length == 0) {
	    		    	Ext.MessageBox.alert('错误', '请选择下级物料');
	    		    	return false;
	    		    }
	    		    // 保存
	    		    Ext.Msg.wait('保存中，请稍后...', '提示');
	    		    Ext.Ajax.request({
                        url: getRootPath() + '/public/product/bom/autosave',
                        params: {nid: lib.bom.nid, recordkey: lib.bom.recordkey, type: lib.bom.type, fa: Ext.encode(faRecord), son: Ext.encode(sonRecord)},
                        method: 'POST',
                        success: function(response, options) {
                        	Ext.Msg.hide();
                    	    window.down('form').close();
                    	    window.close();
                    	    if(lib.bom.type == 'edit') {
                    	    	var data = Ext.JSON.decode(response.responseText);
                    	    	Ext.MessageBox.alert('提示', data.info);
                    	    	lib.bom.grid.getStore().reload();
                    	    }
                        },
                        failure: function(form, action) {
                            Ext.MessageBox.alert('错误', '数据保存失败,'+action.result.info);
                        }
                    });
                }
            }, {
                text: '审批',
                id: 'reviewBtn',
                hidden: true,
                handler: function(){
            	    lib.bom.win.show();
                }
            }, {
                text: '升版状况',
                id: 'updDetailBtn',
                hidden: true,
                handler: function(){
				    var left_ver = (Number(lib.bom.selectedVer)-0.1).toFixed(1);
	            	Ext.create('Ext.window.Window', {
	                    title: 'BOM升版状况',
	                    maximized: true,
	                    maximizable: false,
	                    layout: 'fit',
	                    html: "<iframe src='" + getRootPath() + "/public/product/upddetail?left_code=" + lib.bom.selectedCode + "&right_code=" + lib.bom.selectedCode + "&left_ver=" + left_ver + "&right_ver=" + lib.bom.selectedVer + "' frameborder=0 width=100% height=100%></iframe>"
	                }).show();
                }
            }, {
                text: '关闭',
                handler: function(){
                	var store = lib.bom.rightStore;
                	var updateRecords = store.getUpdatedRecords();
                    var insertRecords = store.getNewRecords();
                    var deleteRecords = store.getRemovedRecords();

                    // 判断是否有修改数据
                    if(updateRecords.length + insertRecords.length + deleteRecords.length > 0){
                    	Ext.MessageBox.confirm('确认', '有修改还未保存，是否放弃修改？', function(button, text) {
                            if (button == 'yes') {
                            	var window = Ext.getCmp('bomWin');
                        	    window.down('form').close();
                        	    window.close();
                            }
                    	});
                    } else {
                    	var window = Ext.getCmp('bomWin');
                	    window.down('form').close();
                	    window.close();
                    }
                }
            }],

            columns: [new Ext.grid.RowNumberer({
		            	  header : "序号",
		            	  width : 50,
		            	  renderer:function(value,metadata,record,rowIndex){
		            	   return rowIndex+1;
		            	  }
            	 }), {
            	    text: 'ID',
                    hidden   : true,
                    editable: false,
                    dataIndex: 'id'
                },{
            	    text: 'PID',
                    hidden   : true,
                    editable: false,
                    dataIndex: 'pid'
                },{
                    text: '物料号',
                    dataIndex: 'code',
                    editable: false,
                    width: 140
                },
                {
                    text     : '状态',
                    width    : 80,
                    sortable : true,
                    hidden   : true,
                    editable: false,
                    dataIndex: 'state'
                },
                {
                    text     : '物料名称',
                    width    : 180,
                    sortable : true,
                    editable : false,
                    dataIndex: 'name',
                    renderer: showTitle
                },
                {
                    text     : '物料描述',
                    width    : 180,
                    sortable : true,
                    editable: false,
                    dataIndex: 'description',
                    renderer: showTitle
                },
                {
                    text     : '数量',
                    width    : 120,
                    sortable : false,
                    dataIndex: 'qty',
                    editor   : {
                	    xtype: 'numberfield',
                        allowNegative : false, // 是否允许输入负数
                        decimalPrecision : 6,  // 输入数字精度
                        value: 1
	                }
                },
                {
                    text     : '器件位置',
                    width    : 180,
                    editor   : 'textfield',
                    dataIndex: 'partposition',
                    renderer : showTitle
                },
                {
                    text     : '替代料',
                    width    : 200,
                    dataIndex: 'replace',
                    renderer: showTitle,
                    editor   : {
	                    xtype: 'triggerfield',
	                    id: 'replace',
	                    triggerCls: 'x-form-search-trigger',
	                    onTriggerClick: function() {
	                        var params = new Array();
	                        params["replace"] = "code";
	                        var grid = this.up('grid');

	                        var winCodeSelect = createCodeSelect(selectsonStore, params, null, null, lib.bom.sonStore, grid);
	                        winCodeSelect.show();
	                    }
	                }
                },
                {
                    text     : '备注',
                    width    : 160,
                    editor   : 'textfield',
                    dataIndex: 'remark',
                    renderer: showTitle
                }
            ]
        });

        lib.bom.rightStore.on("datachanged", function() {
    		if(lib.bom.rightStore.count() > 0) {
    		    form.getSelectionModel().select(0);
    		    records = form.getSelectionModel().getSelection();
    		    record= records[0];

                for (k = 0; k < lib.bom.rightStore.getCount(); k++)
                {
                	lib.bom.rightStore.getAt(k).set('index', k + 1);//model类的set,写入编号
                }
    		}
    	});
        return form;
    };

    function createWin(left, rightUp, rightDown, title, submitFn) {
        var win = new Ext.Window({
            xtype : "window",
            id: 'bomWin',
            title: title,
            border:0,
            modal: true,
            maximized: true,
            layout:"border",
            closeAction: 'destroy',
            //maximizable: true,
            items: [{
                region: 'west',
                layout: {
                    type: 'vbox',
                    align:'stretch'
                },
                items: [{
                    plain: true,
                    margin: '0',
                    layout: 'fit',
                    flex: 1,
                    border: 0,
                    items: [left]
                }]
            }, {
                region: 'center',
                layout: {
                    type: 'vbox',
                    align:'stretch'
                },
                items: [{
                	flex: .8,
                    border: false,
                    split: true,
                    border:0,
                    collapsible: true,
                	layout: {
                        type: 'hbox',
                        align:'stretch'
                    },
                    items: [{
                        plain: true,
                        margin: '0',
                        layout: 'fit',
                        flex: 1,
                        border: 0,
                        items: [rightUp]
                    }]
                }, {
                	flex: 1.2,
                	layout: {
                        type: 'hbox',
                        align:'stretch'
                    },
                    items: [{
                        plain: true,
                        margin: '0',
                        layout: 'fit',
                        flex: 1,
                        border: 0,
                        items: [rightDown]
                    }]
                }]
            }]
        });
        return win;
    };
})(window);
