
Ext.define('files', {
	extend : 'Ext.data.Model',
	idProperty : 'id',
	fields : [ {
		name : "id"
	}, {
		name : "state"
	}, {
		name : "codever"
	}, {
		name : "code"
	}, {
		name : "type_code"
	}, {
		name : "model_id"
	}, {
		name : "dev_model_id"
	}, {
		name : "ver"
	}, {
		name : "name"
	}, {
		name : "type"
	}, {
		name : "size"
	}, {
		name : "path"
	}, {
		name : "view_path"
	}, {
		name : "description"
	}, {
		name : "remark"
	}, {
		name : "archive_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "create_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "update_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "creater"
	}, {
		name : "updater"
	}, {
		name : "mytype"
	}, {
		name : "tag"
	}, {
		name : "file_ids"
	}, {
		name : "review_id"
	}, {
		name : "review_state"
	}, {
		name : "step_name"
	}, {
		name : "step_ename"
	}, {
		name : "newest_ver"
	}, {
		name : "future_ver"
	}, {
		name : "reason"
	}, {
        name : "reason_type"
    }, {
        name : "reason_type_name"
    }, {
    	name : "project_info"
	}, {
		name : "project_no"
	}, {
		name : "project_name"
	}, {
		name : "category"
	}, {
		name : "category_name"
	}, {
		name : "type_name"
	}, {
		name : "send_require"
	}]
});

Ext.define('upload', {
	extend : 'Ext.data.Model',
	idProperty : 'id',
	fields : [ {
		name : "id"
	}, {
		name : "name"
	}, {
		name : "path"
	}, {
		name : "view_path"
	}, {
		name : "size"
	}, {
		name : "type"
	}, {
		name : "description"
	}, {
		name : "remark"
	}, {
		name : "share_id"
	}, {
		name : "share_name"
	}, {
		name : "archive"
	}, {
		name : "del"
	}, {
		name : "share_time"
	}, {
		name : "create_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "update_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "upload_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "creater"
	}, {
		name : "updater"
	}, {
		name : "project_info"
	} ]
});

Ext.define('code', {
	extend : 'Ext.data.Model',
	idProperty : 'id',
	fields : [ {
		name : "id"
	}, {
		name : "prefix"
	}, {
		name : "type_id"
	}, {
		name : "type_code"
	}, {
		name : "model_id"
	}, {
		name : "code"
	}, {
		name : "active"
	}, {
		name : "description"
	}, {
		name : "remark"
	}, {
		name : "tag"
	}, {
		name : "create_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "update_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "creater"
	}, {
		name : "updater"
	}, {
		name : "ver"
	}, {
		name : "dev_model_id"
	}, {
		name : "project_no"
	}, {
		name : "project_name"
	}]
});

Ext.define('apply', {
	extend : 'Ext.data.Model',
	idProperty : 'id',
	fields : [ {
		name : "id"
	}, {
		name : "code_id"
	}, {
		name : "state"
	}, {
		name : "description"
	}, {
		name : "remark"
	}, {
		name : "create_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "update_time",
		type : 'date',
		dateFormat : 'timestamp'
	}, {
		name : "creater"
	}, {
		name : "updater"
	} ]
});

Ext.define('codemaster', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: "id"},
        {name: "code"},
        {name: 'text'}
    ]
});