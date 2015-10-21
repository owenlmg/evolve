var catalogListStore = Ext.create('Ext.data.Store', {
    model: 'Catalog',
    pageSize: 50,
    proxy: {
        type: 'ajax',
        reader: {
            type: 'json',
            root: 'rows',
            totalProperty: 'total'
        },
        url: HOME_PATH + '/public/product/catalog/list'
    },
    listeners: {
    	beforeload: loadCatalogList
    }
});

function loadCatalogList(){
	var key = Ext.getCmp('search_key_batch').getValue();
    var type_id = Ext.getCmp('search_type_id_batch').getValue();
    var developmode_id = Ext.getCmp('search_developmode_id_batch').getValue();
    var series_id = Ext.getCmp('search_series_id_batch').getValue();
    var stage_id = Ext.getCmp('search_stage_id_batch').getValue();
    var active = Ext.getCmp('search_active').getValue();
    var date_from = Ext.Date.format(Ext.getCmp('search_date_from').getValue(), 'Y-m-d');
    var date_to = Ext.Date.format(Ext.getCmp('search_date_to').getValue(), 'Y-m-d');
    var evt_date = Ext.getCmp('search_evt_date').getValue();
    var dvt_date = Ext.getCmp('search_dvt_date').getValue();
    var qa1_date = Ext.getCmp('search_qa1_date').getValue();
    var qa2_date = Ext.getCmp('search_qa2_date').getValue();
    var mass_production_date = Ext.getCmp('search_mass_production_date').getValue();
    
	Ext.apply(catalogStore.proxy.extraParams, {
		search_type: search_type,
		active: active,
		date_from: date_from,
		date_to: date_to,
		key: key,
		evt_date: evt_date,
		dvt_date: dvt_date,
		qa1_date: qa1_date,
		qa2_date: qa2_date,
		mass_production_date: mass_production_date,
		create_user: create_user,
		auditor_id: auditor_id,
		display_deleted: display_deleted,
		type_id: type_id,
		developmode_id: developmode_id,
		series_id: series_id,
		stage_id: stage_id
    });
};

// 批量维护窗口
var batchWin = Ext.create('Ext.window.Window', {
	title: '批量维护',
	id: 'batchWin',
	width: 800,
	modal: true,
	constrain: true,
	layout: 'fit',
	maximizable: true,
	resizable: true,
	closeAction: 'hide',
	items: [batchForm]
});