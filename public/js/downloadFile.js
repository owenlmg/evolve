// 创建下载链接区域
var createFileViewField = function(label, id, file_id, code, ver, file_name, file_path, source, state, role) {
    var icon = "";
    // 从日常维护进入的可以下载作废的文件
    if (file_path && (state != 'Obsolete' || source == 'edit') && (role || source == 'edit')) {
        icon = '<img src="' + getRootPath() + '/public/images/icons/download.png" onclick="download(' + file_id + ', \'' + source + '\')" style="cursor:pointer;"></img>';
    } else {
    	icon = '<img src="' + getRootPath() + '/public/images/icons/download_n.png">';
    }
    var fileType = file_name.substr(file_name.indexOf(".")+1);
    if (file_id && checkFileType(fileType) && (state != 'Obsolete' || source == 'edit')) {
        url = getRootPath() + "/public/dcc/online/?id=" + file_id;
        icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview.png" onclick="javascript:window.open(\'' + url + '\')" style="cursor:pointer;"></img>';
    } else {
    	icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview-gray.png">'
    }
    var len = file_name.match(/[^ -~]/g) == null ? file_name.length : file_name.length + file_name.match(/[^ -~]/g).length ;
    if(len > 50) file_name = "<a style='cursor:default;' title='" + file_name + "'>" + substr(file_name, 50) + "...</a>";
    files = icon + " " + file_name;
    code = code + " V" + ver;

    this.field = Ext.create('Ext.form.FieldContainer', {
        xtype: 'fieldcontainer',
        width: 880,
//        overflowX: 'auto',
        layout: 'hbox',
        items: [{
                xtype: 'displayfield',
                fieldLabel: '文件号',
                name: 'code' + id,
                flex: 1.8,
                value: code
            }, {
                xtype: 'splitter',
                flex: .2
            }, {
                xtype: 'displayfield',
                fieldLabel: '描述',
                flex: .5,
                name: 'description' + id,
                value: ver
            }, {
                xtype: 'displayfield',
                fieldLabel: '产品型号',
                flex: .5,
                name: 'project_no' + id,
                value: ver
            }, {
                xtype: 'splitter',
                hidden: true,
                flex: .2
            }, {
                xtype: 'displayfield',
                fieldLabel: '文件',
                hideLabel: true,
                width: 300,
                flex: 3,
                name: 'name' + id,
                value: files
            }]
    });
    return field;
};

var createFileViewNoCodeField = function(label, id, file_id, code, ver, file_name, file_path, source, state) {
    var icon = "";
    if (file_path && state != 'Obsolete') {
        icon = '<img src="' + getRootPath() + '/public/images/icons/download.png" onclick="download(' + file_id + ', \'' + source + '\')" style="cursor:pointer;"></img>';
    } else {
    	icon = '<img src="' + getRootPath() + '/public/images/icons/download_n.png">';
    }
    var fileType = file_name.substr(file_name.indexOf(".")+1);
    if (file_id && checkFileType(fileType) && state != 'Obsolete') {
        url = getRootPath() + "/public/dcc/online/?id=" + file_id;
        icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview.png" onclick="javascript:window.open(\'' + url + '\')" style="cursor:pointer;"></img>';
    } else {
    	icon += '&nbsp;&nbsp;<img src="' + getRootPath() + '/public/images/icons/text-preview-gray.png">'
    }
    var len = file_name.match(/[^ -~]/g) == null ? file_name.length : file_name.length + file_name.match(/[^ -~]/g).length ;
    if(len > 50) file_name = "<a style='cursor:default;' title='" + file_name + "'>" + substr(file_name, 50) + "...</a>";
    files = icon + " " + file_name;

    this.field = Ext.create('Ext.form.FieldContainer', {
        xtype: 'fieldcontainer',
        width: 680,
//        overflowX: 'auto',
        layout: 'hbox',
        items: [{
                xtype: 'splitter',
                flex: 2
            }, {
                xtype: 'displayfield',
                fieldLabel: '文件',
                hideLabel: true,
                flex: 3,
                name: 'name' + id,
                value: files
            }]
    });
    return field;
};

// 文件下载
var download = function(id, source) {
    window.open(getRootPath() + '/public/dcc/download/download/id/' + id + '/source/' + source);
};

var delFile = function() {
    var fileForm = Ext.getCmp('fileForm');
    var fileField = fileForm.queryById("file");
    var filedownload = fileForm.queryById("filedownload");

    fileField.setDisabled(false);
    fileField.show();
    filedownload.hide();

};

//js获取项目根路径，如： http://localhost:8083/uimcardprj
function getRootPath() {
    //获取当前网址，如： http://localhost:8083/uimcardprj/share/meun.jsp
    var curWwwPath = window.document.location.href;
    //获取主机地址之后的目录，如： uimcardprj/share/meun.jsp
    var pathName = window.document.location.pathname;
    var pos = curWwwPath.indexOf(pathName);
    //获取主机地址，如： http://localhost:8083
    var localhostPaht = curWwwPath.substring(0, pos);
    //获取带"/"的项目名，如：/uimcardprj
    var projectName = pathName.substring(0, pathName.substr(1).indexOf('/') + 1);
    return(localhostPaht + projectName);
}
function substr(str, len)
{
    if(!str || !len) { return ''; }
 
    //预期计数：中文2字节，英文1字节
    var a = 0;
 
    //循环计数
    var i = 0;
 
    //临时字串
    var temp = '';
 
    for (i=0;i<str.length;i++)
    {
        if (str.charCodeAt(i)>255) 
        {
            //按照预期计数增加2
            a+=2;
        }
        else
        {
            a++;
        }
        //如果增加计数后长度大于限定长度，就直接返回临时字符串
        if(a > len) { return temp; }
 
        //将当前内容加到临时字符串
        temp += str.charAt(i);
    }
    //如果全部是单字节字符，就直接返回源字符串
    return str;
}