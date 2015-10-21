function fisker_encode(s) {
    var es = [],
            c = '',
            ec = '';
    s = s.split('');
    for (var i = 0, length = s.length; i < length; i++) {
        c = s[i];
        ec = encodeURIComponent(c);
        if (ec == c) {
            ec = c.charCodeAt().toString(16);
            ec = ('00' + ec).slice(-2);
        }
        es.push(ec);
    }
    return es.join('').replace(/%/g, '').toUpperCase();
}

function showTitle(value, p) {
	if(!value) return "";
    var tip = value.replace(/,/g, '<br />');
    p.tdAttr = 'data-qtip="' + tip + '"';
    return value;
}

/**
 * js获取项目根路径，如： http://localhost:8083/uimcardprj
 */

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
    return (localhostPaht + projectName);
}

function viewBool(value) {
    if (value) {
        return "<img src='" + getRootPath() + "/public/images/icons/tick.png'></img>";
    } else {
        return "<img src='" + getRootPath() + "/public/images/icons/cross.gif'></img>";
    }
}

function checkFileType(type) {
    var supports = ['txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'];
    type = type.replace(/(^\s*)|(\s*$)/g, "");
    for (var i = 0; i < supports.length; i++) {
        if (type === supports[i]) {
            return true;
        }
    }
    return false;
}

function eq(v1, v2) {
	if(v1 == null) v1 = "";
	if(v2 == null) v2 = "";
	if(v1 == v2) return true;
	return false;
}

/**
 * 多文件上传
 * @param obj
 * @param userid
 */
function uploadify(obj, userid) {
	$(obj).uploadify({
		swf: getRootPath() + '/public/js/uploadify/scripts/uploadify.swf',
		uploader:getRootPath() + '/public/dcc/upload/multiupload',
		formData:{
    		'employee_id' : userid
		},
		queueID:'fileQueue',
		buttonText:'选择文件',
		fileObjName : 'file',
		auto : true,
		removeCompleted: false,
		uploadLimit: 99,
		fileSizeLimit: '500MB',
		'onUploadSuccess' : function(file, data, response) {
			if(data) {
				var json = Ext.JSON.decode(data);
				if(json.result) {
				    uploadedFileIds.push(json.id);
				} else {
					$(obj).uploadify('cancel', file.id);
					Ext.Msg.alert('提示', json.info + ':' + file.name);
				}
			}
        },
        'onUploadError' : function(file, errorCode, errorMsg, errorString) {
//             alert('文件 ' + file.name + ' 上传失败: ' + errorString);
        },
		'onQueueComplete': function(queueData) {
        	if (queueData.uploadsErrored) {
        		Ext.Msg.alert('提示', '文件上传错误');
        	} else {
//         		Ext.MessageBox.alert('提示', '文件上传成功');
//         		form.reset();
//                 uploadStore.load();
//                 win.hide();
        	}
		},
		'onFallback': function () { 
			alert("您未安装FLASH控件，无法上传！请安装FLASH控件后再刷新本页面。");
			window.open('http://get.adobe.com/cn/flashplayer/');
		}
    });
}