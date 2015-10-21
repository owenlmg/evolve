// 产品目录角色设置
Ext.define('Roleset', {
    extend: 'Ext.data.Model',
    fields: [{name: "id"}, 
             {name: "active"}, 
             {name: "catalog_id"}, 
             {name: "role_id"}, 
             {name: "role"}, 
             {name: "user_id"}, 
             {name: "user"}, 
             {name: "remark"}]
});

// 角色设置数据源
var rolesetStore = Ext.create('Ext.data.Store', {
 model: 'Roleset',
 proxy: {
     type: 'ajax',
     reader: 'json',
     url: homePath+'/public/product/catalog/getroleset'
 }
});

//必填提示
var required = '<span style="color:red;font-weight:bold" data-qtip="Required">*</span>';

// 根据已选角色ID，初始化角色树选中状态
function setRoleSetTree(role_id, root, node){
	if(root){
		root = catalogRoleTreeStore.getRootNode();
		
		root.eachChild(function(child){
			setRoleSetTree(role_id, false, child);
		});
	}else{
		if(node.hasChildNodes()){
			node.eachChild(function(child){
				setRoleSetTree(role_id, false, child);
			});
		}else{
			if(node.data['id'] == role_id){
				node.set('checked', true);
			}
		}
	}
}

// 重置（取消选中）备选角色树所有节点的选中状态
function resetRoleSetTree(root, node){
	if(root){
		root = catalogRoleTreeStore.getRootNode();
		
		root.eachChild(function(child){
			resetRoleSetTree(false, child);
		});
	}else{
		if(node.hasChildNodes()){
			node.eachChild(function(child){
				resetRoleSetTree(false, child);
			});
		}else{
			node.set('checked', false);
		}
	}
}

var rolesetRowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
    clicksToEdit: 1
});

//角色数据模型
Ext.define('Member', {
    extend: 'Ext.data.Model',
    idProperty: 'id',
    fields: [{name: "user_id"}, 
             {name: "text"}]
});

// 角色数据源
var memberTreeStore = Ext.create('Ext.data.TreeStore', {
    model: 'Member',
    proxy: {
        type: 'ajax',
        reader: 'json',
        url: homePath+'/public/product/catalog/getrolemember'
    }
});

// 角色树面板
var catalogRoleMemberPanel = Ext.create('Ext.tree.Panel', {
	xtype: 'check-tree',
	border: 0,
	maxHeight: 360,
	minHeight: 200,
    useArrows: true,
    rootVisible: false,
    layout: 'fit',
    store: memberTreeStore,
    animate: false
});

var roleMemberWin = Ext.create('Ext.window.Window', {
	title: '项目角色成员管理',
	width: 300,
	modal: true,
	constrain: true,
	layout: 'fit',
	closeAction: 'hide',
	resizable: false,
	items: [{
		xtype: 'hiddenfield',
		id: 'member_roleset_id',
		name: 'member_roleset_id'
	}, {
        region: 'center',
        border: 0,
        items: [catalogRoleMemberPanel]
	}],
	tbar: ['->', {
		iconCls: 'icon-save',
		text: '保存',
		handler: function(){
			var records = catalogRoleMemberPanel.getView().getChecked();
			
			if(records.length > 0){
				var idStr = '';
				
				for(var i = 0; i < records.length; i++){
					if(i == 0){
						idStr = records[i].get('user_id');
					}else{
						idStr += ',' + records[i].get('user_id');
					}
				}
		        
		        var member_roleset_id = Ext.getCmp('member_roleset_id').getValue();

		        Ext.Msg.wait('添加中，请稍后...', '提示');
		        Ext.Ajax.request({
                    url: homePath+'/public/product/catalog/editrolemember',
                    params: {roleset_id: member_roleset_id, idStr: idStr},
                    method: 'POST',
                    success: function(response, options) {
                        var data = Ext.JSON.decode(response.responseText);

                        if(data.success){
                        	Ext.Msg.hide();
                        	roleMemberWin.hide();
                            rolesetStore.reload();
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(response){
                        Ext.MessageBox.alert('错误', '保存失败');
                    }
                });
			}else{
				Ext.MessageBox.alert('错误', '成员不能为空！');
			}
		}
	}, {
		iconCls: 'icon-delete',
		text: '取消',
		handler: function(){
			roleMemberWin.hide();
		}
	}]
});

// 打开角色成员编辑窗口
function openEdit(id, role_id, user_id){
	roleMemberWin.show();
	
	Ext.getCmp('member_roleset_id').setValue(id);
	
	memberTreeStore.load({
        params: {
        	role_id: role_id,
        	user_id: user_id
        }
    });
}

// 角色设置表
var rolesetGrid = Ext.create('Ext.grid.Panel', {
    height: 160,
    border: 0,
    columnLines: true,
    store: rolesetStore,
    tbar: [{
        text: '项目角色配置',
        id: 'roleMemberSetBtn',
        iconCls: 'icon-group',
        handler: function(){
        	catalogRoleWin.show();
        	
        	rolesetStore.reload();
        	resetRoleSetTree(true, null);
        	
        	// 初始化角色树，已添加角色默认选中
        	rolesetStore.each(function(record){
        		setRoleSetTree(record.get('role_id'), true, null);
    		});
        }
    }, {
        text: '保存',
        id: 'roleSetSaveBtn',
        iconCls: 'icon-save',
        handler: function(){
            var updateRecords = rolesetStore.getUpdatedRecords();
            var deleteRecords = rolesetStore.getRemovedRecords();

            // 判断是否有修改数据
            if(updateRecords.length + deleteRecords.length > 0){
                var changeRows = {
                        updated:    [],
                        deleted:    []
                }

                for(var i = 0; i < updateRecords.length; i++){
                    var data = updateRecords[i].data;
                    changeRows.updated.push(data)
                }
                
                for(var i = 0; i < deleteRecords.length; i++){
                    changeRows.deleted.push(deleteRecords[i].data)
                }

                Ext.MessageBox.confirm('确认', '确定保存修改内容？', function(button, text){
                    if(button == 'yes'){
                        var json = Ext.JSON.encode(changeRows);
                        
                        Ext.Msg.wait('保存中，请稍后...', '提示');
                        Ext.Ajax.request({
                            url: homePath+'/public/product/catalog/editroleset',
                            params: {json: json},
                            method: 'POST',
                            success: function(response, options) {
                                var data = Ext.JSON.decode(response.responseText);

                                if(data.success){
                                    Ext.MessageBox.alert('提示', data.info);
                                    rolesetStore.reload();
                                }else{
                                    Ext.MessageBox.alert('错误', data.info);
                                }
                            },
                            failure: function(response){
                                Ext.MessageBox.alert('错误', '保存失败');
                            }
                        });
                    }
                });
            }else{
                Ext.MessageBox.alert('提示', '没有修改任何数据！');
            }
        }
    }, {
        text: '刷新',
        iconCls: 'icon-refresh',
        handler: function(){
        	rolesetStore.reload();
        }
    }],
    viewConfig: {
        stripeRows: false,// 取消偶数行背景色
        getRowClass: function(record) {
            if(record.get('active') == 0){
                return 'gray-row';
            }
        }
    },
    plugins: rolesetRowEditing,
    columns: [{
        xtype: 'rownumberer'
    }, {
    	xtype: 'checkcolumn',
        text: '启用',
        dataIndex: 'active',
        stopSelection: false,
        flex: 0.5
    }, {
    	text: 'ID',
    	align: 'center',
    	hidden: true,
    	dataIndex: 'id',
    	flex: 1
    }, {
        text: '角色ID',
    	align: 'center',
        hidden: true,
        dataIndex: 'role_id',
        flex: 0.5
    }, {
        text: '角色 *',
        dataIndex: 'role',
        flex: 1
    }, {
        text: '成员',
        dataIndex: 'user',
        renderer: function(value, metaData, record, colIndex, store, view) {  
        	return '<img style="cursor:pointer;" title="点击按钮修改成员" onclick="openEdit(' + record.get('id') + ', ' + record.get('role_id') + ', \''+record.get('user_id')+'\');" src="'+homePath+'/public/images/icons/group_edit.png"></img>&nbsp;&nbsp;'+value;
        },
        flex: 2
    }, {
        text: '备注',
        dataIndex: 'remark',
        editor: 'textfield',
        flex: 1.5
    }]
});

// 项目属性维护
var attributeWin = Ext.create('Ext.window.Window', {
 title: '项目属性',
 width: 950,
 modal: true,
 constrain: true,
 layout: 'fit',
 closeAction: 'hide',
 resizable: false,
 items: [{
     region: 'center',
     border: 0,
     split: true,
     items: [{
         xtype: 'form',
         border: 0,
         id: 'catalog_form',
         url: homePath+'/public/product/catalog/editcatalog/attribute/true',
         bodyPadding: '5 5 0',
         fieldDefaults: {
             msgTarget: 'side',
             labelWidth: 75
         },
         items: [{
        	xtype: 'fieldset',
         	title: '销售',
         	collapsible: true,
         	items: [{
            	xtype: 'fieldcontainer',
            	msgTarget : 'side',
                layout: 'hbox',
                defaults: {
                	xtype: 'displayfield',
                    labelStyle: 'font-weight:bold',
                    flex: 1,
                    labelWidth: 90,
                    labelAlign: 'right'
                },
                items: [{
                	name: 'series_id',
                	fieldLabel: '产品系列',
                	renderer: seriesRender
                }, {
                	name: 'active',
                	fieldLabel: '产品状态',
                	renderer: activeRender
                }, {
                	name: 'developmode_id',
                	fieldLabel: '产品开发模式',
                	renderer: modeRender
                }, {
                	name: 'code_customer',
                	fieldLabel: '客户代码'
                }, {
                	name: 'model_customer',
                	fieldLabel: '客户型号'
                }]
             }, {
            	xtype: 'fieldcontainer',
            	msgTarget : 'side',
                layout: 'hbox',
                defaults: {
                	xtype: 'displayfield',
                    labelStyle: 'font-weight:bold',
                    flex: 1,
                    labelWidth: 90,
                    labelAlign: 'right'
                },
                items: [{
                	name: 'model_standard',
                	fieldLabel: '标准产品型号'
                }, {
                	name: 'model_internal',
                	fieldLabel: '内部产品型号'
                }, {
                	name: 'description_customer',
                    flex: 2,
                	fieldLabel: '客户产品描述'
                }]
             }, {
            	xtype: 'fieldcontainer',
            	msgTarget : 'side',
                layout: 'hbox',
                defaults: {
                	xtype: 'displayfield',
                    labelStyle: 'font-weight:bold',
                    flex: 1,
                    labelWidth: 90,
                    labelAlign: 'right'
                },
                items: [{name: 'description', fieldLabel: '产品描述'}]
             }]
         }, {
        	xtype: 'fieldset',
          	title: 'PM',
          	collapsible: true,
          	items: [{
            	xtype: 'fieldcontainer',
            	msgTarget : 'side',
                layout: 'hbox',
                defaults: {
                    allowBlank: false,
                    labelStyle: 'font-weight:bold',
                    flex: 1,
                    labelWidth: 70,
                    labelAlign: 'right'
                },
                items: [{
                	xtype: 'hiddenfield',
                	name: 'id',
                	id: 'catalog_id'
                }, {
                	xtype:'textfield',
                	name: 'code', 
                	fieldLabel: '产品代码'
                }, {
                	xtype:'textfield',
                	name: 'code_old', 
                	labelWidth: 90,
                	fieldLabel: '旧产品代码'
                }, {
                	name: 'stage_id', 
                	xtype:'combobox',
                    displayField: 'name',
                    valueField: 'id',
                    triggerAction: 'all',
                    lazyRender: true,
                    queryMode: 'local',
                    afterLabelTextTpl: required,
                    editable: false,
                	store: stageStore,
                	fieldLabel: '产品阶段'
                }]
             }, {
            	xtype: 'fieldcontainer',
            	msgTarget : 'side',
                layout: 'hbox',
                defaults: {
                	xtype: 'datefield',
                	format: 'Y-m-d',
                	editable: false,
                    labelStyle: 'font-weight:bold',
                    flex: 1,
                    width: 140,
                    labelWidth: 70,
                    labelAlign: 'right'
                },
                items: [{
                	name: 'evt_date',
                	fieldLabel: 'EVT通过'
                }, {
                	name: 'date_dvt',
                	fieldLabel: 'DVT通过'
                }, {
                	name: 'qa1_date',
                	fieldLabel: 'QA1通过'
                }, {
                	name: 'qa2_date',
                	fieldLabel: 'QA2通过'
                }, {
                	name: 'mass_production_date',
                	fieldLabel: '进入量产'
                }]
             }, {
                 xtype:'tabpanel',
                 border: 0,
                 margin: '2 0 0 0',
                 plain: true,
                 tabBar: {
                 	height: 24,
                 	defaults: {
                 		height: 22
                     }
                 },
                 activeTab: 0,
                 items:[{
                     title:'角色设置',
                     region: 'center',
                     margin: '0 0 5 0',
                     border: 0,
                     layout: 'fit',
                     plain: true,
                     items: [rolesetGrid]
                 }, {
                     cls: 'x-plain',
                     title: '备注',
                     border: 0,
                     margin: '0 0 5 0',
                     layout: 'fit',
                     items: {
                         xtype: 'htmleditor',
                         enableFont: false,
                         height: 160,
                         name: 'remark'
                     }
                 }]
             }]
         }],
         buttons: [{
             text: '保存',
             id: 'productInfoSaveBtn',
             handler: function() {
                 var form = this.up('form').getForm();
                 
                 if(form.isValid()){
                 	Ext.MessageBox.confirm('确认', '确定保存？', function(button, text){
                         if(button == 'yes'){
                        	 form.submit({
                                 waitMsg: '保存中，请稍后...',
                                 success: function(form, action) {
                                	 var data = action.result;
                                	 
                                     if(data.success){
                                         Ext.MessageBox.alert('提示', data.info);
                                         attributeWin.hide();
                                         catalogStore.reload();
                                         catalogGrid.getSelectionModel().clearSelections(); 
                                     }else{
                                         Ext.MessageBox.alert('错误', data.info);
                                     }
                                 },
                                 failure: function(form, action) {
                                     Ext.MessageBox.alert('错误', action.result.info);
                                 }
                             });
                         }
                     });
                 }
             }
         },{
             text: '取消',
             handler: function() {
                 this.up('form').getForm().reset();
                 attributeWin.hide();
             }
         }]
     }]
 }]
});