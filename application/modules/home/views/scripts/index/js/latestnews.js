/**
 * 最新资讯
 */
Ext.define('News', {
    extend: 'Ext.data.Model',
    fields: [{name: 'id'}, 
             {name: 'title'}, 
             {name: 'new'}, 
             {name: 'create_time',type: 'date',dateFormat: 'timestamp'}]
});

var newRender = function(val){
	if(val){
		return '<img src="' + homePath + '/public/images/icons/new.gif"></img>';
	}
};

var news01Store = Ext.create('Ext.data.Store', {
    model: 'News',
    pageSize: 10,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/hra/news/gettitlelist/public/' + newsPublic
    },
    autoLoad: true,
    listeners: {
    	beforeload: function(store){
    		Ext.apply(store.proxy.extraParams, {
    			type: 1
            });
        }
    }
});

var news02Store = Ext.create('Ext.data.Store', {
    model: 'News',
    pageSize: 10,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/hra/news/gettitlelist/public/' + newsPublic
    },
    autoLoad: true,
    listeners: {
    	beforeload: function(store){
    		Ext.apply(store.proxy.extraParams, {
    			type: 4
            });
        }
    }
});

var news03Store = Ext.create('Ext.data.Store', {
    model: 'News',
    pageSize: 10,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/hra/news/gettitlelist/public/' + newsPublic
    },
    autoLoad: true,
    listeners: {
    	beforeload: function(store){
    		Ext.apply(store.proxy.extraParams, {
    			type: 3
            });
        }
    }
});

var news04Store = Ext.create('Ext.data.Store', {
    model: 'News',
    pageSize: 10,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: homePath + '/public/hra/news/gettitlelist/public/' + newsPublic
    },
    autoLoad: true,
    listeners: {
    	beforeload: function(store){
    		Ext.apply(store.proxy.extraParams, {
    			type: 2
            });
        }
    }
});

//渲染查看公告按钮
var titleRender = function(val, cellmeta, record){
	cellmeta.tdAttr = 'data-qtip="' + val + '"';
	return '<a style="cursor:pointer;" target="_blank" href="' + homePath + '/public/hra/news/view/id/' + record.get('id') + '">' + val + '</a>';
};

var news01Grid = Ext.create('Ext.grid.Panel', {
	title: '企业动态',
	border: 0,
    //hideHeaders: true,
    layout: 'fit',
    store: news01Store,
    columns: [{
    	text: 'ID',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'new',
        renderer: newRender,
        flex: 1
    }, {
        text: '标题',
        dataIndex: 'title',
        renderer: titleRender,
        flex: 5
    }, {
        text: '发布日期',
        dataIndex: 'create_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d'),
        flex: 2
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: news01Store,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: ''
    })
});

var news02Grid = Ext.create('Ext.grid.Panel', {
	title: '培训信息',
	border: 0,
    //hideHeaders: true,
    layout: 'fit',
    store: news02Store,
    columns: [{
    	text: 'ID',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'new',
        renderer: newRender,
        flex: 1
    }, {
        text: '标题',
        dataIndex: 'title',
        renderer: titleRender,
        flex: 5
    }, {
        text: '发布日期',
        dataIndex: 'create_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d'),
        flex: 2
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: news02Store,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: ''
    })
});

var news03Grid = Ext.create('Ext.grid.Panel', {
	title: '版本日志',
	border: 0,
    //hideHeaders: true,
    layout: 'fit',
    store: news03Store,
    columns: [{
    	text: 'ID',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'new',
        renderer: newRender,
        flex: 1
    }, {
        text: '标题',
        dataIndex: 'title',
        renderer: titleRender,
        flex: 5
    }, {
        text: '发布日期',
        dataIndex: 'create_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d'),
        flex: 2
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: news03Store,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: ''
    })
});

var news04Grid = Ext.create('Ext.grid.Panel', {
	title: '企业文化',
	border: 0,
    //hideHeaders: true,
    layout: 'fit',
    store: news04Store,
    columns: [{
    	text: 'ID',
        dataIndex: 'id',
        hidden: true,
        flex: 1
    }, {
        dataIndex: 'new',
        renderer: newRender,
        flex: 1
    }, {
        text: '标题',
        dataIndex: 'title',
        renderer: titleRender,
        flex: 5
    }, {
        text: '发布日期',
        dataIndex: 'create_time',
        align: 'center',
        renderer : Ext.util.Format.dateRenderer('Y-m-d'),
        flex: 2
    }],
    bbar: Ext.create('Ext.PagingToolbar', {
        store: news04Store,
        displayInfo: true,
        displayMsg: '',
        emptyMsg: ''
    })
});