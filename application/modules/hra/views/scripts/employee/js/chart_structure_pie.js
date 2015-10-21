Ext.define('Structure', {
    extend: 'Ext.data.Model',
    fields: [{name: "name"}, 
             {name: "qty", type: 'int'}]
});

var structureStore = Ext.create('Ext.data.Store', {
    model: 'Structure',
    proxy: {
        type: 'ajax',
        reader: 'json'
    }
});

var structureChart = Ext.create('Ext.chart.Chart', {
    xtype: 'chart',
    animate: true,
    store: structureStore,
    shadow: true,
    legend: {
        position: 'right'
    },
    insetPadding: 20,
    theme: 'Base:gradients',
    series: [{
        type: 'pie',
        field: 'qty',
        donut: 35,
        showInLegend: true,
        tips: {
          trackMouse: true,
          width: 140,
          height: 28,
          renderer: function(storeItem, item) {
            // calculate percentage.
            var total = 0;
            structureStore.each(function(rec) {
                total += rec.get('qty');
            });
            this.setTitle(storeItem.get('name') + ' [ ' + storeItem.get('qty') + ' ] : ' + Math.round(storeItem.get('qty') / total * 100) + '%');
          }
        },
        highlight: {
        	segment: {
        		margin: 20
        	}
        },
        label: {
            field: 'name',
            display: 'rotate',
            contrast: true,
            font: '18px Arial'
        }
    }]
});

var chartPanel = Ext.create('widget.panel', {
    width: 750,
    height: 400,
    border: 0,
    layout: 'fit',
    tbar: [{
    	xtype: 'displayfield',
    	value: '<b>当前类别：<b>'
    }, {
    	xtype: 'displayfield',
    	id: 'chart_title',
    	value: '学历'
    }, '->', {
    	text: '职位类别',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('职位类别');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/post_type';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '学历',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('学历');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/education';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '技术职称等级',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('技术职称等级');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/professional_qualifications';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '年龄',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('年龄');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/age';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '性别',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('性别');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/sex';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '入职年限',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('入职年限');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/in';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '工作年限',
        handler: function(){
        	Ext.getCmp('chart_title').setValue('工作年限');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/seniority';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }, {
        text: '地区',
        handler: function(){
    	Ext.getCmp('chart_title').setValue('地区');
            structureStore.getProxy().url = homePath+'/public/hra/employee/getstructure/option/area';
        	structureStore.load();
        	chartStructurePieWin.show();
        }
    }],
    items: structureChart
});

var chartStructurePieWin = Ext.create('Ext.window.Window', {
	title: '统计-人员结构分析',
	maximizable: true,
	modal: true,
	constrain: true,
	closeAction: 'hide',
	layout: 'fit',
	items: chartPanel
});