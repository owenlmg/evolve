var importWin = Ext.create('Ext.window.Window', {
    title: '导入',
    modal: true,
    constrain: true,
    closeAction: 'hide',
    layout: 'fit',
    fieldDefaults: {
        labelAlign: 'left',
        labelWidth: 90,
        anchor: '100%'
    },
    items: [Ext.create('Ext.form.Panel', {
        id: 'importForm',
    	border: 0,
        url: homePath+'/public/erp/bpartner/importcontact',
        bodyPadding: '2 2 0',
        fieldDefaults: {
        	msgTarget: 'side',
            labelAlign: 'right',
            labelWidth: 60,
            anchor: '100%'
        },
        items: [{
        	xtype: 'filefield',
            name: 'csv',
            allowBlank: false,
            fieldLabel: 'CSV',
            buttonText: '选择文件'
        }]
    })],
    buttons: [{
    	text: '提交',
        handler: function() {
            var form = Ext.getCmp('importForm').getForm();

            if(form.isValid()){
            	form.submit({
                    waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                        var result = action.result;

                        if(result.success){
                        	var data = result.data;
                        	
                        	form.reset();
                            importWin.hide();
                            
                            contactRowEditing.cancelEdit();

                        	for(var i = 0; i < data.length; i++){
                    			var r = Ext.create('Contact', {
                    				contact_active: true,
                    				contact_default: false,
                    				contact_name: data[i].name,
                    				contact_post: data[i].post,
                    				contact_tel: data[i].tel,
                    				contact_fax: data[i].fax,
                    				contact_email: data[i].email,
                    				contact_person_liable: data[i].person_liable,
                    				contact_area_code: data[i].area_code,
                    				contact_country: data[i].country,
                    				contact_area: data[i].area,
                    				contact_area_city: data[i].area_city,
                    				contact_address: data[i].address,
                    				contact_zip_code: data[i].zip_code,
                    				contact_remark: data[i].remark
                                });

                    			contactStore.insert(contactStore.getCount(), r);
                    		}
                        	
                        	contactGrid.getSelectionModel().select(contactStore.getCount() - 1);
                        }else{
                            Ext.MessageBox.alert('错误', data.info);
                        }
                    },
                    failure: function(form, action){
                        Ext.MessageBox.alert('错误', action.result.info);
                    }
                });
            }
        }
    }, {
        text: '取消',
        handler: function() {
        	Ext.getCmp('importForm').getForm().reset();
            importWin.hide();
        }
    }]
});