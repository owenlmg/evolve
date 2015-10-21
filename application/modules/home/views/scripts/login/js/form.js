function resetPwdSub(){
	var form = resetPwdForm;
    
    if (form.isValid()) {
        var pwd1 = Ext.getCmp('pwd1').getValue();
        var pwd2 = Ext.getCmp('pwd2').getValue();

        if(pwd1 == pwd2){
            // 检查密码强度，密码长度必须大于等于6位且包含英文字母及数字
        	if(pwd1.length < 6){
        		Ext.Msg.alert('错误', '密码长度不能小于6位，请重新输入！');
        	}else if((!pwd1.match(/[a-z]/) && !pwd1.match(/[A-Z]/)) || !pwd1.match(/\d/)){
        		Ext.Msg.alert('错误', '密码必须包含字母和数字，请重新输入！');
        	}else{
                form.submit({
                	waitMsg: '提交中，请稍后...',
                    success: function(form, action) {
                        if(action.result){
                            Ext.Msg.alert('Success', action.result.info);
                            window.location.href = homePath+'/public/home/login';
                        }else{
                            Ext.Msg.alert('错误', action.result.info);
                        }
                    },
                    failure: function(form, action) {
                        Ext.Msg.alert('错误', action.result.info);
                    }
                });
            }
        }else{
        	Ext.Msg.alert('错误', '密码不一致，请重新输入！');
        }
    }
}

var resetPwdForm = Ext.create('Ext.form.Panel', {
    margin: '15 0 0 0',
    bodyPadding: 10,
    url: homePath+'/public/login/savepassword',
    defaultType: 'textfield',
    fieldDefaults: {
        labelAlign: 'right',
        labelWidth: 100,
        labelStyle: 'font-weight:bold'
    },
    items: [{
        xtype: 'hiddenfield',
        value: user_id,
        name: 'user_id',
        id: 'user_id',
        allowBlank: false
    }, {
        xtype: 'hiddenfield',
        value: key,
        name: 'key',
        id: 'key',
        allowBlank: false
    }, {
    	xtype: 'displayfield',
    	id: 'reset_pwd_tips'
    }, {
        inputType: 'password',
        fieldLabel: '输入新密码',
        name: 'pwd1',
        id: 'pwd1',
        allowBlank: false
    }, {
        inputType: 'password',
        fieldLabel: '再次输入',
        name: 'pwd2',
        id: 'pwd2',
        allowBlank: false,
        listeners: {
        	specialKey :function(field,e){
                if (e.getKey() == Ext.EventObject.ENTER){
                	resetPwdSub();
                }
            }
        }
    }, {
        xtype: 'label',
        text: '（密码长度必须大于等于6位且包含英文字母及数字）',
        style: {
            color: '#FF0000'
        }
    }],

    // Reset and Submit buttons
    buttons: [{
        text: '返回登录',
        handler: function() {
            window.location.href = homePath+'/public/home/login';
        }
    }, {
        text: '修改密码',
        formBind: true,
        disabled: true,
        handler: function() {
        	resetPwdSub();
        }
    }]
});

var resetPwdWin = Ext.create('Ext.window.Window', {
    closable: false,
    draggable: false,
    resizable:false,
    modal: true,
    width: 350,
    height: 240,
    y: 75,
    layout: {
        type: 'border'
    },
    items: [{
        title: '修改密码',
        region: 'center',
        width: 200,
        split: true,
        collapsible: false,
        floatable: false,
        layout: 'fit',
        items: resetPwdForm
    }]
});