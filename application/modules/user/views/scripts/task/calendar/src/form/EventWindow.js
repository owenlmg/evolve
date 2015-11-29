/**
 * @class Ext.calendar.form.EventWindow
 * @extends Ext.Window
 * <p>A custom window containing a basic edit form used for quick editing of events.</p>
 * <p>This window also provides custom events specific to the calendar so that other calendar components can be easily
 * notified when an event has been edited via this component.</p>
 * @constructor
 * @param {Object} config The config object
 */
Ext.define('Ext.calendar.form.EventWindow', {
    extend: 'Ext.window.Window',
    alias: 'widget.eventeditwindow',

    requires: [
        'Ext.form.Panel',
        'Ext.calendar.util.Date',
        'Ext.calendar.data.EventModel',
        'Ext.calendar.data.EventMappings'
    ],

    constructor: function (config) {
        var formPanelCfg = {
            xtype: 'form',
            fieldDefaults: {
                msgTarget: 'side',
                labelWidth: 65
            },
            frame: false,
            bodyStyle: 'background:transparent;padding:5px 10px 10px;',
            bodyBorder: false,
            border: false,
            items: [{
                itemId: 'cid',
                name: Ext.calendar.data.EventMappings.CalendarId.name,
                fieldLabel: 'CID',
                value: 1,
                xtype: 'textfield',
                hidden: true,
                anchor: '98%'
            }, {
                itemId: 'title',
                name: Ext.calendar.data.EventMappings.Title.name,
                fieldLabel: '名称',
                xtype: 'textfield',
                allowBlank: false,
                emptyText: '任务名称...',
                anchor: '98%'
            }, {
                itemId: 'parent',
                name: Ext.calendar.data.EventMappings.Parent.name,
                fieldLabel: '父任务',
                xtype: 'combobox',
                editable: false,
                emptyText: '上级任务...',
                displayField: 'title',
                valueField: 'id',
                anchor: '98%',
                store: Ext.create('Ext.data.Store', {
                    model: Ext.define('task', {
                        extend: 'Ext.data.Model',
                        idProperty: 'id',
                        fields: [{name: "id"},
                            {name: "title"}
                        ]
                    }),
                    proxy: {
                        type: 'ajax',
                        reader: {
                            root: 'evts'
                        },
                        url: getRootPath() + '/public/user/task/current'
                    },
                    autoLoad: true
                })
            },
                {
                    xtype: 'daterangefield',
                    itemId: 'date-range',
                    showAllDay: false,
                    name: 'dates',
                    anchor: '98%',
                    fieldLabel: '时间'
                }, {
                    itemId: 'notes',
                    name: Ext.calendar.data.EventMappings.Notes.name,
                    fieldLabel: '描述',
                    xtype: 'textarea',
                    allowBlank: false,
                    emptyText: '任务描述...',
                    grow: true,
                    growMax: 150,
                    anchor: '98%'
                }, {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    anchor: '98%',
                    items: [{
                        xtype: 'combobox',
                        fieldLabel: '重要程度',
                        name: Ext.calendar.data.EventMappings.Important.name,
                        editable: false,
                        value: '重要',
                        emptyText: '重要',
                        store: [['非常重要', '非常重要'], ['重要', '重要'], ['不重要', '不重要']],
                        anchor: '33%'
                    }, {
                        xtype: 'combobox',
                        fieldLabel: '优先级',
                        editable: false,
                        name: Ext.calendar.data.EventMappings.Priority.name,
                        value: '紧急',
                        emptyText: '紧急',
                        store: [['非常紧急', '非常紧急'], ['紧急', '紧急'], ['不紧急', '不紧急']],
                        anchor: '33%'
                    }, {
                        xtype: 'combobox',
                        fieldLabel: '分配方式',
                        editable: false,
                        name: Ext.calendar.data.EventMappings.Type.name,
                        emptyText: '独立',
                        store: [['独立', '独立'], ['协作', '协作']],
                        anchor: '31%'
                    }]
                }, {
                    xtype: 'textfield',
                    hidden: true,
                    id: 'Responsible_id',
                    name: 'Responsible_id'
                }, {
                    xtype: 'employeebobox',
                    fieldLabel: '责任人',
                    itemId: 'Responsible',
                    labelWidth: 65,
                    allowBlank: false,
                    id: 'Responsible',
                    name: 'Responsible',
                    anchor: '98%',
                    store: config.responsible
                }, {
                    xtype: 'textfield',
                    hidden: true,
                    id: 'Follow_id',
                    name: 'Follow_id'
                }, {
                    xtype: 'employeebobox',
                    fieldLabel: '关注者',
                    itemId: 'Follow',
                    store: config.employeeStore,
                    labelWidth: 65,
                    id: 'Follow',
                    name: 'Follow',
                    anchor: '98%'
                }, {
                    xtype: 'textfield',
                    itemId: 'Location',
                    hidden: true
                }]
        };

        if (config.processStore ) {
            this.processStore = config.processStore;
            delete config.processStore;
            var me = this;

            var rowEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    'beforeedit' : function(editor, e) {
                        if(e.record.data.id > 0) {
                            return false;
                        }
                    }
                }
            });
            var grid = {
                xtype: 'fieldcontainer',
                itemId: 'content',
                name: Ext.calendar.data.EventMappings.Content.name,
                fieldLabel: '进度',
                autoScoll:true,
                anchor: '98%',
                items: [Ext.create('Ext.grid.Panel', {
                    store: this.processStore,
                    border: true,
                    columnLines: true,
                    maximizable: true,
                    maximized: true,
                    plugins: [rowEditing],
                    viewConfig:{
                        autoFill:true
                        //forceFit:true
                    },
                    columns: [
                        {text: 'ID',hidden: true,dataIndex: 'id'},
                        {text: '日期', dataIndex: 'update_time', width: 140,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
                            editor: {
                                xtype: 'datefield',
                                format: 'Y-m-d H:i'
                            }
                        },
                        {text: '进度(%)', dataIndex: 'rate', width: 60,
                            editor: {
                                xtype: 'numberfield',
                                minValue: 0,
                                maxValue: 100
                            }
                        },
                        {text: '状态', dataIndex: 'status', width: 70,
                            editor: {
                                xtype: 'combobox',
                                emptyText: '进行中',
                                store: [['进行中', '进行中'], ['暂停', '暂停'], ['延迟', '延迟'], ['取消', '取消'], ['完成', '完成']]
                            }
                        },
                        {text: '说明备注', dataIndex: 'remark',width:300,renderer:showTitle,
                            editor: {
                            }
                        }
                    ],
                    tbar: [{
                        text: '添加进度',
                        id: 'process-btn',
                        handler : function() {
                            rowEditing.cancelEdit();

                            var r = Ext.create('process', {
                                update_time : new Date(),
                                rate:null
                            });

                            me.processStore.insert(0, r);
                            rowEditing.startEdit(0, 1);
                        }
                    }]
                })]
            };

            formPanelCfg.items.push(grid);
        }

        if (config.calendarStore && false) {
            this.calendarStore = config.calendarStore;
            delete config.calendarStore;

            formPanelCfg.items.push({
                xtype: 'calendarpicker',
                itemId: 'calendar',
                name: '任务类型',
                anchor: '98%',
                store: this.calendarStore
            });
        }

        this.callParent([Ext.apply({
                titleTextAdd: '添加任务',
                titleTextEdit: '修改任务',
                width: 700,
                height: 450,
                autoScroll:true,
                autocreate: true,
                border: true,
                closeAction: 'hide',
                modal: false,
                resizable: false,
                buttonAlign: 'left',
                savingMessage: '保存中...',
                deletingMessage: '删除中...',

                defaultFocus: 'title',
                onEsc: function (key, event) {
                    event.target.blur(); // Remove the focus to avoid doing the validity checks when the window is shown again.
                    this.onCancel();
                },

                fbar: [{
                    xtype: 'tbtext',
                    hidden: true,
                    text: '<a href="#" id="tblink">Edit Details...</a>'
                },
                    '->',
                    {
                        itemId: 'delete-btn',
                        text: '删除任务',
                        disabled: false,
                        handler: this.onDelete,
                        scope: this,
                        hideMode: 'offsets'
                    },
                    {
                        itemId: 'save-btn',
                        text: '保存',
                        disabled: false,
                        handler: this.onSave,
                        scope: this
                    },
                    {
                        itemId: 'cancel-btn',
                        text: '关闭',
                        disabled: false,
                        handler: this.onCancel,
                        scope: this
                    }],
                items: formPanelCfg
            },
            config)]);
    },

    // private
    newId: 10000,

    // private
    initComponent: function () {
        this.callParent();

        this.formPanel = this.items.items[0];

        this.addEvents({
            /**
             * @event eventadd
             * Fires after a new event is added
             * @param {Ext.calendar.form.EventWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was added
             */
            eventadd: true,
            /**
             * @event eventupdate
             * Fires after an existing event is updated
             * @param {Ext.calendar.form.EventWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was updated
             */
            eventupdate: true,
            /**
             * @event eventdelete
             * Fires after an event is deleted
             * @param {Ext.calendar.form.EventWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was deleted
             */
            eventdelete: true,
            /**
             * @event eventcancel
             * Fires after an event add/edit operation is canceled by the user and no store update took place
             * @param {Ext.calendar.form.EventWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was canceled
             */
            eventcancel: true,
            /**
             * @event editdetails
             * Fires when the user selects the option in this window to continue editing in the detailed edit form
             * (by default, an instance of {@link Ext.calendar.EventEditForm}. Handling code should hide this window
             * and transfer the current event record to the appropriate instance of the detailed form by showing it
             * and calling {@link Ext.calendar.EventEditForm#loadRecord loadRecord}.
             * @param {Ext.calendar.form.EventWindow} this
             * @param {Ext.calendar.EventRecord} rec The {@link Ext.calendar.EventRecord record} that is currently being edited
             */
            editdetails: true
        });
    },

    // private
    afterRender: function () {
        this.callParent();

        this.el.addCls('ext-cal-event-win');

        Ext.get('tblink').on('click', this.onEditDetailsClick, this);

        this.titleField = this.down('#title');
        this.dateRangeField = this.down('#date-range');
        this.calendarField = this.down('#calendar');
        this.deleteButton = this.down('#delete-btn');
        this.saveButton = this.down('#save-btn');
        this.cancelButton = this.down('#cancel-btn');
        this.processButton = this.down('#process-btn');
    },

    // private
    onEditDetailsClick: function (e) {
        e.stopEvent();
        this.updateRecord(this.activeRecord, true);
        this.fireEvent('editdetails', this, this.activeRecord, this.animateTarget);
    },

    /**
     * Shows the window, rendering it first if necessary, or activates it and brings it to front if hidden.
     * @param {Ext.data.Record/Object} o Either a {@link Ext.data.Record} if showing the form
     * for an existing event in edit mode, or a plain object containing a StartDate property (and
     * optionally an EndDate property) for showing the form in add mode.
     * @param {String/Element} animateTarget (optional) The target element or id from which the window should
     * animate while opening (defaults to null with no animation)
     * @return {Ext.Window} this
     */
    show: function (o, animateTarget) {
        // Work around the CSS day cell height hack needed for initial render in IE8/strict:
        var me = this,
            anim = (Ext.isIE8 && Ext.isStrict) ? null : animateTarget,
            M = Ext.calendar.data.EventMappings;

        this.callParent([anim, function () {
            me.titleField.focus(true);
        }]);


        var rec,
            f = this.formPanel.form;


        // 初始化
        this.processButton['show']();
        this.saveButton['show']();
        this.setReadOnly(false);
        if (o.data) {
            rec = o;
            this.setTitle(rec.phantom ? this.titleTextAdd : this.titleTextEdit);
            f.loadRecord(rec);
            // 按钮控制
            var relation = o.data.Relation;
            if(relation == 1) {
                // 创建者 可以删除、修改、更新进度
                this.processButton['show']();
                this.deleteButton[o.data && o.data[M.EventId.name] ? 'show' : 'hide']();
                this.saveButton['show']();
                this.setReadOnly(false);
            } else if(relation == 2) {
                // 责任人 可以更新进度
                this.processButton['show']();
                this.deleteButton['hide']();
                this.saveButton['show']();
                this.setReadOnly(true);
            } else if(relation == 3) {
                // 关注着 只能看
                this.processButton['hide']();
                this.deleteButton['hide']();
                this.saveButton['hide']();
                this.setReadOnly(true);
            } else {
                // 下级 只能看
                this.processButton['hide']();
                this.deleteButton['hide']();
                this.saveButton['hide']();
                this.setReadOnly(true);
            }
            // 取消和完成的任务只读
            if(o.data.State == '取消' || o.data.State == '完成') {
                this.processButton['hide']();
                this.deleteButton['hide']();
                this.saveButton['hide']();
                this.setReadOnly(true);
            }
        }
        else {
            this.deleteButton[o.data && o.data[M.EventId.name] ? 'show' : 'hide']();
            this.setTitle(this.titleTextAdd);

            var start = o[M.StartDate.name],
                end = o[M.EndDate.name] || Ext.calendar.util.Date.add(start, {hours: 9});

            rec = Ext.create('Ext.calendar.data.EventModel');
            rec.data[M.StartDate.name] = start;
            rec.data[M.EndDate.name] = end;
            rec.data[M.IsAllDay.name] = !!o[M.IsAllDay.name] || start.getDate() != Ext.calendar.util.Date.add(end, {millis: 1}).getDate();

            // 初始化
            if(o.initData) {
                rec.data = Ext.apply(rec.data, o.initData);
            }

            f.reset();
            f.loadRecord(rec);
        }

        if (this.calendarStore && false) {
            this.calendarField.setValue(rec.data[M.CalendarId.name]);
        }
        this.dateRangeField.setValue(rec.data);
        this.activeRecord = rec;

        return this;
    },
    setReadOnly: function(flag) {
        var f = this.formPanel.form;
        Ext.suspendLayouts();
        f.getFields().each (function (field) {
            if(field.name != 'update_time' && field.name != 'rate'
                && field.name != 'status' && field.name != 'remark') {
                field.setReadOnly (flag);
            }
        });
        Ext.resumeLayouts();
    },

    // private
    roundTime: function (dt, incr) {
        incr = incr || 15;
        var m = parseInt(dt.getMinutes(), 10);
        return dt.add('mi', incr - (m % incr));
    },

    // private
    onCancel: function () {
        this.cleanup(true);
        this.fireEvent('eventcancel', this);
    },

    // private
    cleanup: function (hide) {
        if (this.activeRecord && this.activeRecord.dirty) {
            this.activeRecord.reject();
        }
        delete this.activeRecord;

        if (hide === true) {
            // Work around the CSS day cell height hack needed for initial render in IE8/strict:
            //var anim = afterDelete || (Ext.isIE8 && Ext.isStrict) ? null : this.animateTarget;
            this.hide();
        }
    },

    // private
    updateRecord: function (record, keepEditing) {
        var fields = record.fields,
            values = this.formPanel.getForm().getValues(),
            name,
            M = Ext.calendar.data.EventMappings,
            obj = {};

        fields.each(function (f) {
            name = f.name;
            if (name in values) {
                obj[name] = values[name];
            }
        });

        var dates = this.dateRangeField.getValue();
        obj[M.StartDate.name] = dates[0];
        obj[M.EndDate.name] = dates[1];
        obj[M.IsAllDay.name] = dates[2];

        record.beginEdit();
        record.set(obj);

        if (!keepEditing) {
            record.endEdit();
        }

        return this;
    },

    // private
    onSave: function () {
        if (!this.formPanel.form.isValid()) {
            return;
        }
        if (!this.updateRecord(this.activeRecord)) {
            this.onCancel();
            return;
        }
        // 进度
        var insertRecords = this.processStore.getNewRecords();
        var records = [];
        if(insertRecords.length > 0) {
            for(var i = 0; i < insertRecords.length; i++){
                var data = insertRecords[i].data;

                if(data['update_time'] == '' || data['update_time'] == '' || data['update_time'] == 0 || data['status'] == '') {
                    var msg = "请完善进度信息";
                    Ext.MessageBox.alert('提示', msg);
                    return;
                }
                records.push(data);
            }
        }
        this.activeRecord.data.Process = Ext.JSON.encode(records);
        this.activeRecord.data.Notes = this.activeRecord.data.Notes + "1 ";
        // 其他数据无修改时触发提交
        if(records.length > 0) {
            this.activeRecord.data.Location = Math.random();
        }
        //this.fireEvent(this.activeRecord.phantom ? 'eventadd' : 'eventupdate', this, this.activeRecord, this.animateTarget);
        this.fireEvent('eventadd', this, this.activeRecord, this.animateTarget);

        // Clear phantom and modified states.
        this.activeRecord.commit();
    },

    // private
    onDelete: function () {
        var me = this;
        Ext.MessageBox.confirm('删除确认', '确认删除此任务？', function(btn) {
            if(btn == 'yes') {
                me.fireEvent('eventdelete', me, me.activeRecord, me.animateTarget);
            }
        });
    }
});