Ext.define('mbom', {
    extend: 'Ext.data.Model',
    idProperty: 'sid',
    fields: [{ name: "sid" },
        { name: "recordkey" },
        { name: "code" },
        { name: "ver" },
        { name: "qty" },
        { name: "remark" },
        { name: "project_no" },
        { name: "project_no_name" },
        { name: "state" },
        { name: "name" },
        { name: "description" },
        { name: "partposition" },
        { name: "replace" },
        { name: "archive_time", type: 'date', dateFormat: 'timestamp' }
    ]
});

/*
 * 物料用量模块
 * 调用方法：
 * var materielDosage = lib.product.materielDosage({code: code});
 * materielDosage.show();
 */
var lib = lib || {};
lib.product = lib.product || {};
(function(window, undefined) {

    lib.product.materielDosage = function(opts) {
        return new MaterielDosage(opts);
    };
    var MaterielDosage = function(opts) {
        // 1 创建Grid
        var code = opts.code;

        this.gridPanel = createGrid.call(this, code);
        this.win = createWindow.call(this, this.gridPanel, code);
    };

    MaterielDosage.prototype = {
        show: function() {
            this.win.show();
        }
    };

    function getStore(code) {
        var store = Ext.create('Ext.data.Store', {
            model: 'mbom',
            proxy: {
                type: 'ajax',
                reader: 'json',
                url: getRootPath() + '/public/product/bom/getmaterieldosage/code/' + code
            },
            autoLoad: true
        });
        return store;
    }

    // 实体文件选择grid
    function createGrid(code) {
        var store = getStore(code);
        var grid = Ext.create('Ext.grid.Panel', {
            store: store,
            width: 1000,
            border:0,
            viewConfig:
            {
                forceFit:false,
                emptyText:'<div style="text-align:center; padding:20px">暂无数据</div>',
                deferEmptyText:false
            },
            columns: [{
                    text: 'ID',
                    width: 50,
                    hidden: true,
                    dataIndex: 'id'
                }, {
                    text: 'BOM号',
                    width: 140,
                    dataIndex: 'code'
                }, {
                    text: '版本',
                    width: 50,
                    dataIndex: 'ver'
                }, {
                    text: '状态',
                    width: 60,
                    dataIndex: 'state'
                }, {
                    text: '用量',
                    width: 50,
                    dataIndex: 'qty'
                }, {
                    text: '物料名称',
                    width: 160,
                    dataIndex: 'name',
                    renderer: showTitle
                }, {
                    text: '描述',
                    width: 180,
                    dataIndex: 'description',
                    renderer: showTitle
                }, {
                    text: '产品型号',
                    width: 140,
                    dataIndex: 'project_no_name',
                    renderer: showTitle
                }, {
                    text: '器件位置',
                    width: 120,
                    dataIndex: 'partposition',
                    renderer: showTitle
                }, {
                    text: '替代料',
                    width: 120,
                    dataIndex: 'replace',
                    renderer: showTitle
                }, {
                    text: '归档时间',
                    width: 120,
                    dataIndex: 'archive_time',
                    renderer : Ext.util.Format.dateRenderer('Y-m-d H:i:s')
                }, {
                    text: '备注',
                    width: 120,
                    dataIndex: 'remark'
                }]
        });
        return grid;
    }

    function createWindow(grid, code) {
        var win = new Ext.Window({
            xtype: "window",
            border:0,
            title: '物料用量查询，物料号：' + code,
            height: 400,
            width: 1000,
            modal: true,
            layout: 'fit',
            items: [grid]
        });
        return win;
    }
})(window);