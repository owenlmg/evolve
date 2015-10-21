/**
 * 待办事项
 */
Ext.define('backlog', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: ["id", "type", "count"]
});

var backlogStore = Ext.create('Ext.data.Store', {
    model: 'backlog',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath + '/public/admin/backlog/getlist'
    },
    autoLoad: true
});

var backlogGrid = Ext.create('Ext.grid.Panel', {
    store: backlogStore,
    border: 0,
    //hideHeaders: true,
    columnLines: true,
    columns: [{
        text: '内容',
        flex: 2,
        dataIndex: 'type',
        renderer: function(value) {
            switch (value) {
                case 'files':
                    return '文件归档审批';
                case 'materiel':
                    return '物料代码申请审批'
                case 'materiel_desc':
                    return '物料代码变更审批'
                case 'materiel_transfer':
                    return '物料代码转化审批'
                case 'product_add':
                    return '产品中心-新建'
                case 'purchse_req_add':
                    return '采购申请-新建'
                case 'purchse_order_add':
                    return '采购订单-新建'
                case 'sale_price_add':
                    return '销售价格清单-新建'
                case 'sale_order_add':
                    return '销售订单-新建'
                case 'attendance_vacation':
                    return '请假申请'
                case 'attendance_overtime':
                    return '加班申请'
                case 'bom':
                    return '新BOM归档审批'
                case 'updbom':
                    return 'BOM升版审批'
                case 'code_apply':
                    return '文件编码申请审批'
            }
        }
    }, {
        text: '数量',
        flex: 1,
        dataIndex: 'count'
    }, {
        text: '处理',
        flex: 1,
        renderer: function(value, p, record) {
            return "<a href='javascript:void(0)' >查看</a>";
        }
    }],
    listeners: {
        cellClick: function(grid, rowIdx, colIdx, evt) {
            if (colIdx == 2) {
                var selection = grid.getSelectionModel().getSelection();
                record = selection[0];
                var text = "待办事项";
                var flag = true;
                var getTabPage;
                var url;
                switch (record.data.type) {
                    case 'files':
                        text = "待办事项（文件归档）";
                        url = homePath + "/public/dcc/mine?personal=1";
                        break;
                    case 'materiel':
                        text = "待办事项（物料归档）";
                        url = homePath + "/public/product/apply?personal=3";
                        break;
                    case 'materiel_desc':
                        text = "待办事项（物料变更）";
                        url = homePath + "/public/product/desc?personal=3";
                        break;
                    case 'materiel_transfer':
                        text = "待办事项（物料转化）";
                        url = homePath + "/public/product/transfer?personal=3";
                        break;
                    case 'product_add':
                        text = "待办事项（产品中心）";
                        url = homePath + "/public/product/catalog?personal=4";
                        break;
                    case 'purchse_req_add':
                        text = "待办事项（采购申请）";
                        url = homePath + "/public/erp/purchse_req?personal=5";
                        break;
                    case 'purchse_order_add':
                        text = "待办事项（采购订单）";
                        url = homePath + "/public/erp/purchse_order?personal=6";
                        break;
                    case 'sale_price_add':
                        text = "待办事项（销售价格申请）";
                        url = homePath + "/public/erp/sale_price?personal=7";
                        break;
                    case 'sale_order_add':
                        text = "待办事项（销售订单申请）";
                        url = homePath + "/public/erp/sale_order?personal=8";
                        break;
                    case 'attendance_vacation':
                        text = "待办事项（请假申请）";
                        url = homePath + "/public/user/attendance/index/active_tab/2";
                        break;
                    case 'attendance_overtime':
                        text = "待办事项（加班申请）";
                        url = homePath + "/public/user/attendance/index/active_tab/3";
                        break;
                    case 'bom':
                        text = "待办事项（新BOM）";
                        url = homePath + "/public/product/newbom?personal=3";
                        break;
                    case 'updbom':
                        text = "待办事项（BOM升版）";
                        url = homePath + "/public/product/updbom?personal=3";
                        break;
                    case 'code_apply':
                        text = "待办事项（文件编码申请）";
                        url = homePath + "/public/dcc/code?personal=3";
                        break;
                }
                // 判断将打开或跳转到指定标签
                for (var i = 0; i < workingArea.items.getCount(); i++) {
                    var item = Ext.getCmp(workingArea.id).items.items[i];
                    if (item.title == text) {
                        flag = false;
                        getTabPage = item;
                        break;
                    }
                }

                if (flag) {
                    workingArea.add({
                        closable: true,
                        html: '<iframe style="height:100%;width:100%;border:none;" src="' + url + '"></iframe>',
                        title: text
                    }).show();
                } else {
                    workingArea.setActiveTab(getTabPage);
                }
            }
        }
    }
});