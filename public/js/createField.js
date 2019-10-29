Ext.define('comb', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{
            name: "id"
        }, {
            name: "option_key"
        }, {
            name: "option_value"
        }]
});

// 删除自定义字段
var removeIntelligenceField = function(form) {
	var fieldArr = new Array();
	form.cascade(function(f) {
		if (!f.isXType('form') && f.isFormField) {
			var field = f.name;
			if (field.indexOf('intelligenceField') === 0 || field.indexOf('intelligenceid') === 0) {
	            fieldArr.push(f);
	        }
        }
	});
	for(var k in fieldArr) {
		form.remove(fieldArr[k]);
	}
    var forms = form.getValues(false);
    for (var field in forms) {
        if (field.indexOf('intelligenceField') === 0 || field.indexOf('intelligenceid') === 0) {
            form.remove(field);
        }
    }
    if (form.down('panel') !== null && ('intelligenceHr' === form.down('panel').id || 'intelligencePanel' === form.down('panel').id)) {
        form.remove(form.down('panel'));
    }
    var item = Ext.ComponentQuery.query('#intelligencePanel, #intelligenceHr', form);
    for (var i = 0; i < item.length; i++) {
        form.remove(item[i]);
    }
};

// 删除自定义DisplayField
var removeIntelligenceDisplay = function(form) {
    var forms = form.items.items;
    var ids = new Array();
    var i = 0;
    // 先取出要删除的ID，再统一删除，若每次都删除，会导致下一次删除的时候form结构已经变了
    for (var property in forms) {
        if (forms.hasOwnProperty(property)) {
            var field = forms[property];
            if (field.id.indexOf('intelligenceField') === 0) {
                ids[i++] = field.id;
            }
        }
    }
    for (var i = 0; i < ids.length; i++) {
        form.remove(ids[i]);
    }
};

/**
 * 读取智能表单数据，创建表单
 * @param {form} form form
 * @param {int} id id
 */
var createForm = function(form, id, filed_width) {
    // 先删除Form之前添加的自定义字段
    removeIntelligenceField(form);
    Ext.Msg.wait('加载中，请稍后...', '提示');
    Ext.Ajax.request({
        url: getRootPath() + '/public/admin/form/getattr',
        params: {
            id: id
        },
        method: 'POST',
        success: function(response, options) {
            var data = Ext.JSON.decode(response.responseText);
            if (data.length > 0) {
                form.add(createHiddenFiled("intelligenceid", id));
            }
            form.add(createHr());
            for (var i = 0; i < data.length; i++) {
                var row = data[i];
                var label = row.name,
                        defaultValue = row.default_value,
                        width = filed_width ? filed_width : row.default_width,
                        length = row.length,
                        enumlistid = row.enumlist,
                        allowBlank = row.nullable,
                        multiSelect = row.multi,
                        name = "intelligenceField" + row.id;


                switch (row.type) {
                    case 'enum':
                        var store = Ext.create('Ext.data.Store', {
                            model: 'comb',
                            proxy: {
                                type: 'ajax',
                                reader: 'json',
                                url: getRootPath() + '/public/admin/form/getcombo/option/' + enumlistid
                            },
                            autoLoad: true
                        });

                        form.add(createCombo(label, name, defaultValue, width, store, allowBlank, multiSelect));
                        break;
                    case 'varchar':
                        form.add(createTextField(label, name, defaultValue, width, allowBlank, length));
                        break;
                    case 'textarea':
                        form.add(createTextArea(label, name, defaultValue, width, allowBlank, length));
                        break;
                    case 'int':
                        form.add(createInt(label, name, defaultValue, width, allowBlank));
                        break;
                    case 'double':
                        form.add(createDouble(label, name, defaultValue, width, allowBlank));
                        break;
                    case 'date':
                        form.add(createDate(label, name, defaultValue, width, allowBlank));
                        break;
                    case 'datetime':
                        form.add(createDateTime(label, name, defaultValue, width, allowBlank));
                        break;
                    default:
                        break;
                }
            }
            // 显示
            Ext.Msg.hide();
            form.show();
        },
        failure: function(form, action) {
            Ext.MessageBox.alert('错误', action.result.info);
        }
    });
};

/**
 * 读取智能表单数据，创建表单
 * @param {form} form form
 * @param {int} id id
 * @param {Object} opts opts
 */
var createIntelligenceForm = function(form, id, opts, isValidation, cb, params) {
    if(isValidation === undefined) {
        isValidation = true;
    }
    // 先删除Form之前添加的自定义字段
    removeIntelligenceField(form);
    Ext.Msg.wait('加载中，请稍后...', '提示');
    Ext.Ajax.request({
        url: getRootPath() + '/public/admin/form/getattr',
        params: {
            id: id
        },
        method: 'POST',
        success: function(response) {
            var data = Ext.JSON.decode(response.responseText);
            if (data.length > 0) {
                form.add(createHiddenFiled("intelligenceid", id));
            }
            form.add(createHr());
//            var filed_width = opts.filed_width;
            if (typeof opts === 'object' && opts.length > 1 && data.length >= 1) {
                // [date, datetime, double, enum, int, textarea, varchar]
                var big = ['textarea', 'varchar', 'enum'];
                var small = ['data', 'datatime', 'double', 'int'];
                var dataArray = new Array();
                // 行数
                var rowNumber = Math.ceil(data.length / opts.length);
                // 列数
                var columnNumber = opts.length;
                // 创建多个container
                var container = new Array();
                var c = Ext.create('Ext.form.Panel', {
                    border: false,
                    id: 'intelligencePanel',
                    items: []
                });
                form.add(c);
                for (var i = 0; i < rowNumber; i++) {
                    container[i] = createContainer();
                    c.add(container);
                }
                var k = 0;
                var column = 0;
                var r = 0;
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    dataArray[i] = row.type;
                    addField(container[r], row, opts[column++], isValidation);
                    if (++k >= columnNumber) {
                        k = 0;
                        r++;
                        column = 0;
                    }
                }
            }
            // 显示
            Ext.Msg.hide();
            if (cb !== null && cb !== undefined && typeof(cb) == "function" ) {
            	if(params) {
            		cb(params);
            	} else {
            		cb();
            	}
            }
            form.show();
        },
        failure: function(form, action) {
            Ext.MessageBox.alert('错误', action.result.info);
        }
    });
};

function addField(container, row, flex, isValidation) {
    var label = row.name,
            defaultValue = row.default_value,
            width = row.default_width,
            length = row.length,
            enumlistid = row.enumlist,
            allowBlank = row.nullable,
            multiSelect = row.multi,
            name = "intelligenceField" + row.id;
    if(!isValidation) {
        allowBlank = true;
    }

    switch (row.type) {
        case 'enum':
            var store = Ext.create('Ext.data.Store', {
                model: 'comb',
                proxy: {
                    type: 'ajax',
                    reader: 'json',
                    url: getRootPath() + '/public/admin/form/getcombo/option/' + enumlistid
                },
                autoLoad: true
            });

            container.add(createCombo(label, name, defaultValue, width, store, allowBlank, multiSelect, flex));
            break;
        case 'varchar':
            container.add(createTextField(label, name, defaultValue, width, allowBlank, length, flex));
            break;
        case 'textarea':
            container.add(createTextArea(label, name, defaultValue, width, allowBlank, length, flex));
            break;
        case 'int':
            container.add(createInt(label, name, defaultValue, width, allowBlank, flex));
            break;
        case 'double':
            container.add(createDouble(label, name, defaultValue, width, allowBlank, flex));
            break;
        case 'date':
            container.add(createDate(label, name, defaultValue, width, allowBlank, flex));
            break;
        case 'datetime':
            container.add(createDateTime(label, name, defaultValue, width, allowBlank, flex));
            break;
        default:
            break;
    }
    return container;
}

function createContainer() {
    this.myText = Ext.create('Ext.form.FieldContainer', {
        layout: 'hbox',
        items: []
    });
    return myText;
}

//读取智能表单数据，创建表单
var createDisplay = function(form, menu, func) {
    // 先删除Form之前添加的自定义字段
    removeIntelligenceDisplay(form);
    Ext.Msg.wait('加载中，请稍后...', '提示');
    Ext.Ajax.request({
        url: getRootPath() + '/public/admin/form/getval',
        params: {
            menu: menu
        },
        method: 'POST',
        success: function(response, options) {
            var data = Ext.JSON.decode(response.responseText);
            for (var i = 0; i < data.length; i++) {
                var row = data[i];
                var label = row.name,
                        value = row.option_value ? row.option_value : row.value,
                        name = "intelligenceField" + row.id;

                form.add(createDisplayField(label, name, value));
            }
            if (func !== null && func !== undefined) {
                func();
            }

            Ext.Msg.hide();
            form.show();
        },
        failure: function(form, action) {
            Ext.MessageBox.alert('错误', action.result.info);
        }
    });
};

/**
 * 创建处理记录grid
 * @param table 表名
 * @param id id
 * @param autoLoad 是否自动加载
 */
function createRecordGrid(table, id, autoLoad) {
    Ext.define('record', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'type', type: 'string'},
            {name: 'table_name', type: 'string'},
            {name: 'table_id', type: 'int'},
            {name: 'handle_user', type: 'string'},
            {name: 'handle_time', type: 'date', dateFormat: 'timestamp'},
            {name: 'action', type: 'string'},
            {name: 'result', type: 'string'},
            {name: 'remark', type: 'string'}
        ]
    });

    var store = Ext.create('Ext.data.Store', {
        model: 'record',
        proxy: {
            type: 'ajax',
            reader: 'json',
            url: getRootPath() + '/public/dcc/record/getrecord'
        },
        autoLoad: false
    });

    if (autoLoad === undefined || autoLoad) {
        store.load({
            params: {
                table: table,
                id: id
            }
        });
    }

    this.recordGrid = Ext.create('Ext.grid.Panel', {
        store: store,
        border: true,
        columnLines: true,
        maximizable: true,
        maximized: true,
        columns: [
            {text: 'ID',hidden: true,dataIndex: 'id', width: 40},
            {text: '姓名', dataIndex: 'handle_user', width: 60},
            {text: '时间', dataIndex: 'handle_time', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120},
            {text: '类型', dataIndex: 'action', width: 60},
            {text: '结果', dataIndex: 'result', width: 60},
            {text: '意见', dataIndex: 'remark', editor: 'textfield', flex: 1, renderer: showTitle }
        ]
    });

    return recordGrid;
}

function createHr() {
    this.myText = Ext.create('Ext.form.Panel', {
        xtype: "panel",
        id: 'intelligenceHr',
        name: 'intelligenceHr',
        border: false,
        height: 10,
        html: '<hr width="100%">'
    });
    return myText;
}

/**
 * 创建展示域
 * @param label
 * @param name
 * @param defaultValue
 * @return
 */
function createDisplayField(label, name, defaultValue) {
    this.myText = Ext.create('Ext.form.field.Display', {
        fieldLabel: label, // 动态label
//		id : name,
        name: name, // 动态名字
        value: defaultValue // 动态默认值
    });
    return myText;
}

/**
 * 创建隐藏域
 * @param name
 * @param defaultValue
 * @return
 */
function createHiddenFiled(name, defaultValue) {
    this.myText = Ext.create('Ext.form.field.Text', {
        id: name,
        name: name,
        hidden: true,
        value: defaultValue
    });
    return myText;
}

/**
 * 创建文本框组件
 * @param label
 * @param name
 * @param defaultValue
 * @param width
 * @param allowBlank
 * @param maxLength
 * @return
 */
function createTextField(label, name, defaultValue, width, allowBlank, maxLength, flex) {
    this.myText = Ext.create('Ext.form.field.Text', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank
    });
    if (maxLength && maxLength > 0) {
        myText.maxLength = maxLength;
    }
    return myText;
}

//创建多行文本框组件
function createTextArea(label, name, defaultValue, width, allowBlank, maxLength, flex) {
    this.myText = Ext.create('Ext.form.field.TextArea', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        grow: true,
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank
    });
    if (maxLength) {
        myText.maxLength = maxLength;
    }
    return myText;
}

//创建数字框组件
function createInt(label, name, defaultValue, width, allowBlank, flex) {
    this.myText = Ext.create('Ext.form.field.Number', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank,
        allowDecimals: false
    });
    return myText;
}

//创建数字（小数）框组件
function createDouble(label, name, defaultValue, width, allowBlank, flex) {
    this.myText = Ext.create('Ext.form.field.Number', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank,
        allowDecimals: true
    });
    return myText;
}

//创建日期框组件
function createDate(label, name, defaultValue, width, allowBlank, flex) {
    this.myText = Ext.create('Ext.form.field.Date', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        format: 'Y-m-d',
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank
    });
    return myText;
}

//创建日期时间框组件
function createDateTime(label, name, defaultValue, width, allowBlank, flex) {
    this.myText = Ext.create('Ext.form.field.Date', {
        flex: flex,
        fieldLabel: label, // 动态label
        id: name,
        name: name, // 动态名字
        format: 'Y-m-d h:i:s',
        value: defaultValue, // 动态默认值
        allowBlank: allowBlank
    });
    return myText;
}

// 创建 ComboBox 组件
function createCombo(label, name, defaultValue, width, store, allowBlank, multiSelect, flex) {
    this.myText = Ext.create('Ext.form.ComboBox', {
        flex: flex,
        id: name,
        name: name, // 动态名字
        store: store, // 获取下拉选的数据
        afterLabelTextTpl: allowBlank ? '' : '<span style="color:red;font-weight:bold" data-qtip="Required">*</span>',
        fieldLabel: label, // 动态label
        queryMode: 'local',
        typeAhead: true,
        editable: false,
        multiSelect: multiSelect,
        triggerAction: 'all',
        displayField: 'option_value',
        valueField: 'option_key',
        emptyText: '请选择',
        allowBlank: allowBlank,
        blankText: '不能为空!',
        listeners: {
            afterrender: function(t, o) {
                t.value = defaultValue;
            },
            change: function(t, o) {
                var raw = this.rawValue.split(',');
                if(raw.length <= 1) return;
                var vs = new Array();
                this.getStore().each(function(r) {
                    var key = r.data.option_key
                    var value = r.data.option_value;
                    for (var i = 0; i < raw.length; i++) {
                        if (raw[i] == key) {
                            vs.push(value);
                        }
                    }
                });
                this.setRawValue(vs.join(','));
            }
        }
    });
    return myText;
}

/**
 * js获取项目根路径，如： http://localhost:8083/uimcardprj
 */
function getRootPath() {
    //获取当前网址，如： http://localhost:8083/uimcardprj/share/meun.jsp
    var curWwwPath = window.document.location.href;
    //获取主机地址之后的目录，如： uimcardprj/share/meun.jsp
    var pathName = window.document.location.pathname;
    var pos = curWwwPath.indexOf(pathName);
    //获取主机地址，如： http://localhost:8083
    var localhostPaht = curWwwPath.substring(0, pos);
    //获取带"/"的项目名，如：/uimcardprj
    var projectName = pathName.substring(0, pathName.substr(1).indexOf('/') + 1);
    return(localhostPaht + projectName);
}