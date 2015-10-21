// 角色数据模型
Ext.define('Role', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: "id"}, 
             {name: "parentId"}, 
             {name: "name"}, 
             {name: "text"}, 
             {name: "member"}, 
             {name: "description"}, 
             {name: "remark"}, 
             {name: "create_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "update_time",type: 'date',dateFormat: 'timestamp'}, 
             {name: "creater"}, 
             {name: "updater"}, 
             {name: "active"}]
});

// 角色数据源
var catalogRoleTreeStore = Ext.create('Ext.data.TreeStore', {
    model: 'Role',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/admin/right/getrole/option/data/active/true'
    }
});

// 角色树面板
var catalogRolePanel = Ext.create('Ext.tree.Panel', {
	xtype: 'check-tree',
	maxHeight: 360,
	border: 0,
	minHeight: 200,
    useArrows: true,
    rootVisible: false,
    layout: 'fit',
    store: catalogRoleTreeStore,
    animate: false
});

//项目角色维护窗口
var catalogRoleWin = Ext.create('Ext.window.Window', {
	title: '选择项目角色',
	width: 400,
	modal: true,
	constrain: true,
	layout: 'fit',
	closeAction: 'hide',
	resizable: false,
	items: [{
        region: 'center',
        border: 0,
        items: [catalogRolePanel]
	}],
	tbar: [{
        xtype: 'hiddenfield',
        id: 'isExpand',
        value: 0
    }, {
		iconCls: 'icon-view-expand',
    	id: 'expand_or_collapse',
    	text: '展开',
        scope: this,
        handler: function(){
            var btn = Ext.getCmp('expand_or_collapse');
            var isExpand = Ext.getCmp('isExpand').getValue();

            if(isExpand == 0){
            	Ext.getCmp('isExpand').setValue(1)
            	
            	catalogRolePanel.expandAll();

            	btn.setIconCls('icon-view-collapse');
            	btn.setText('折叠');
            }else{
            	Ext.getCmp('isExpand').setValue(0)
            	
            	catalogRolePanel.collapseAll();

            	btn.setIconCls('icon-view-expand');
            	btn.setText('展开');
            }
        }
	}, '->', {
		iconCls: 'icon-save',
		text: '保存',
		handler: function(){
			var records = catalogRolePanel.getView().getChecked();
			
			if(records.length > 0){
				var idStr = '';
				
				for(var i = 0; i < records.length; i++){
					if(i == 0){
						idStr = records[i].get('id');
					}else{
						idStr += ',' + records[i].get('id');
					}
				}
		        
		        var catalog_id = Ext.getCmp('catalog_id').getValue();

		        Ext.Msg.wait('添加中，请稍后...', '提示');
		        Ext.Ajax.request({
                    url: homePath+'/public/product/catalog/addrole',
                    params: {catalog_id: catalog_id, idStr: idStr},
                    method: 'POST',
                    success: function(response, options) {
                        var data = Ext.JSON.decode(response.responseText);

                        if(data.success){
                        	Ext.Msg.hide();
                        	catalogRoleWin.hide();
                            rolesetStore.reload();
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(response){
                        Ext.MessageBox.alert('错误', '保存提交失败');
                    }
                });
			}else{
				Ext.MessageBox.alert('警告', '请选择角色！');
			}
		}
	}, {
		iconCls: 'icon-delete',
		text: '取消',
		handler: function(){
			catalogRoleWin.hide();
		}
	}]
});