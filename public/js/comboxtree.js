Ext.define("Ext.ux.comboboxtree", {
    extend: "Ext.form.field.Picker",
    requires: ["Ext.tree.Panel"],
    alias: 'widget.combotree',
    initComponent: function() {
        var self = this;
        Ext.apply(self, {
            fieldLabel: self.fieldLabel,
            labelWidth: self.labelWidth,
            store: self.store
        });
        self.callParent();
    },
    createPicker: function() {
        var self = this;
        var store = this.store;
        var fun;
        /*var store = Ext.create('Ext.data.TreeStore', {
         proxy: {
         type: 'ajax',
         url: self.storeUrl
         },
         sorters: [{
         property: 'leaf',
         direction: 'ASC'
         },
         {
         property: self.displayField,
         direction: 'ASC'
         }],
         root: {
         id: self.rootId,
         text: self.rootText
         },
         nodeParam: self.nodeParam
         });*/
        self.picker = new Ext.tree.Panel({
            height: 300,
            autoScroll: true,
            floating: true,
            focusOnToFront: false,
            shadow: true,
            ownerCt: this.ownerCt,
            useArrows: true,
            store: store,
            rootVisible: false,
            tbar: new Ext.Toolbar({  
                buttonAlign : 'center',  
                items : [{
                	xtype : 'textfield',
                	emptyText : '搜索...',
                	enableKeyEvents : true,
                	listeners: {
                        keyup: function (field, e) {
                        	var text = field.value;  
                        	if(text) {
                        		clearTimeout(fun);
                                fun = setTimeout(function () {
                                	self.store.load({params:{name:text}});
                                }, 500);
                        		
                        	}
                        },
                        specialKey :function(field,e){
                        	var text = field.value; 
                            if (e.getKey() == Ext.EventObject.ENTER){
                            	self.store.load({params:{name:text}});
                            }
                        }
                    }
                }]  
            })
        });
        self.picker.on({
            checkchange: function(record, checked) {
                var checkModel = self.checkModel;
                if (checkModel == 'double') {
                    var root = self.picker.getRootNode();
                    root.cascadeBy(function(node) {
                        if (node.get(self.displayField) != record.get(self.displayField)) {
                            if (node.get('checked') != undefined) {
                                node.set('checked', false);
                            }
                        }
                    });
                    if (record.get('leaf') && checked) {

                        self.setId(record.get(self.valueField)); // 隐藏值
                        self.setValue(record.get(self.displayField)); // 显示值
                    } else {
                        record.set('checked', false);
                        self.setId(''); // 隐藏值
                        self.setValue(''); // 显示值
                    }
                } else {

                    var cascade = self.cascade;

                    if (checked == true) {
                        if (cascade == 'both' || cascade == 'child' || cascade == 'parent') {
                            if (cascade == 'child' || cascade == 'both') {
                                if (!record.get("leaf") && checked)
                                    record.cascadeBy(function(record) {
                                        record.set('checked', true);
                                    });

                            }
                            if (cascade == 'parent' || cascade == 'both') {
                                pNode = record.parentNode;
                                for (; pNode != null; pNode = pNode.parentNode) {
                                    pNode.set("checked", true);
                                }
                            }

                        }

                    } else if (checked == false) {
                        if (cascade == 'both' || cascade == 'child' || cascade == 'parent') {
                            if (cascade == 'child' || cascade == 'both') {
                                if (!record.get("leaf") && !checked)
                                    record.cascadeBy(function(record) {

                                        record.set('checked', false);

                                    });
                            }

                        }

                    }

                    var records = self.picker.getView().getChecked(),
                            names = [],
                            values = [];
                    Ext.Array.each(records,
                            function(rec) {
                                var onlyLeaf = self.onlyLeaf;
                                if (onlyLeaf) {
                                    if (rec.get("leaf")) {
                                        names.push(rec.get(self.displayField));
                                        values.push(rec.get(self.valueField));
                                    }
                                } else {
                                    names.push(rec.get(self.displayField));
                                    values.push(rec.get(self.valueField));
                                }
                            });
                    self.setId(values.join(',')); // 隐藏值
                    self.setValue(names.join(',')); // 显示值
                }

            },
            itemclick: function(tree, record, item, index, e, options) {
                var checkModel = self.checkModel;

                if (checkModel == 'single') {
                    if (record.get('leaf')) {
//                        self.setId(record.get(self.valueField)); // 隐藏值
//                        self.setValue(record.get(self.displayField)); // 显示值
                    } else {
                        self.setId(''); // 隐藏值
                        self.setValue(''); // 显示值
                    }
                }

            }
        });
        return self.picker;
    },
    alignPicker: function() {
        var me = this,
                picker, isAbove, aboveSfx = '-above';
        if (this.isExpanded) {
            picker = me.getPicker();
            if (me.matchFieldWidth) {
                picker.setWidth(me.bodyEl.getWidth());
            }
            if (picker.isFloating()) {
                picker.alignTo(me.inputEl, "", me.pickerOffset); // ""->tl
                isAbove = picker.el.getY() < me.inputEl.getY();
                me.bodyEl[isAbove ? 'addCls' : 'removeCls'](me.openCls + aboveSfx);
                picker.el[isAbove ? 'addCls' : 'removeCls'](picker.baseCls + aboveSfx);
            }
        }
    },
    setId: function(node) {
        if (this.ownerCt) {
        	if(typeof this.up('form') != 'undefined') {
	            this.up('form').getForm().findField(this.hiddenName).setValue(node);
	        } else {
	        	if(Ext.getCmp(this.hiddenName)) {
	        		Ext.getCmp(this.hiddenName).setValue(node);
	        	}
	        }
        }
    },
    setShowValue: function(val) {
        var temp = this.getValue();
        var allValue = "";

        //调用父类方法
        this.setValue(temp);
    }
});

/**
 * Add basic filtering to Ext.tree.Panel. Add as a mixin:
 *  mixins: {
 *      treeFilter: 'WMS.view.TreeFilter'
 *  }
 */
Ext.define('WMS.view.TreeFilter', {
    filterByText: function(text) {
        this.filterBy(text, 'text');
    },
    /**
     * Filter the tree on a string, hiding all nodes expect those which match and their parents.
     * @param The term to filter on.
     * @param The field to filter on (i.e. 'text').
     */
    filterBy: function(text, by) {
        this.clearFilter();
        var view = this.getView(),
            me = this,
            nodesAndParents = [];
        // Find the nodes which match the search term, expand them.
        // Then add them and their parents to nodesAndParents.
        this.getRootNode().cascadeBy(function(tree, view){
            var currNode = this;
 
            if(currNode && currNode.data[by] && currNode.data[by].toString().toLowerCase().indexOf(text.toLowerCase()) > -1) {
                me.expandPath(currNode.getPath());
                while(currNode.parentNode) {
                    nodesAndParents.push(currNode.id);
                    currNode = currNode.parentNode;
                }
            }
        }, null, [me, view]);
        // Hide all of the nodes which aren't in nodesAndParents
        this.getRootNode().cascadeBy(function(tree, view){
            var uiNode = view.getNodeByRecord(this);
            if(uiNode && !Ext.Array.contains(nodesAndParents, this.id)) {
                Ext.get(uiNode).setDisplayed('none');
            }
        }, null, [me, view]);
    },
    clearFilter: function() {
        var view = this.getView();
        this.getRootNode().cascadeBy(function(tree, view){
            var uiNode = view.getNodeByRecord(this);
            if(uiNode) {
                Ext.get(uiNode).setDisplayed('table-row');
            }
        }, null, [this, view]);
    }
});