Glizy.oop.declare("glizy.FormEdit.FormEditRepeatMediaGroup", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    id: null,
    idParent: null,
    children: [],
    isCollapsable: null,
    minRec: null,
    maxRec: null,
    noAddRowButton: null,
    noEmptyMessage: null,
    customAddRowLabel: null,
    sortable: null,
    glizyOpt: null,
    form: null,
    readOnly: null,

    originalId: function () {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    },
    
    initialize: function (element, glizyOpt, form, addBtnId, idParent) {
        this.$super(element);
        this.idParent = idParent;

        this.id = element.attr('id');
        this.glizyOpt = glizyOpt;
        this.form = form;

        if (addBtnId === undefined) {
            addBtnId = this.id;
        }

        var self = this;

        this.getOptions();
        this.addClass('GFEFieldset');
        if (this.minRec > 0 ) {
            this.addClass('required');
        }
        var $fields = this.$element.children(':not(legend)');
        var $fieldSet = this.$element;

		 	if(this.noEmptyMessage) {
				$fieldSet.children('legend').before('<div class="border-legend"></div>');
			}

        // TODO: spostare tutta la logica di template in un file appaorte (es handlebars.js)
        if (!this.noAddRowButton && !this.readOnly) {
            var label = this.customAddRowLabel ? this.customAddRowLabel : GlizyLocale.Repeater.addRecord;
            this.$element.append('<div class="GFEFooter"><div class="GFEButtonContainer center"><div id="'+addBtnId+'-addRowBtn" class="btn GFEAddRow"><i class="fa fa-plus"></i> '+label+'</div></div><div class="GFEStatusContainer">' + GlizyLocale.Repeater.minRecords + this.minRec + (this.maxRec ? (GlizyLocale.Repeater.maxRecords + this.maxRec) : '') + '</div><div class="GFESideClearer"></div></div>');
        } else {
            this.$element.append('<div class="GFEFooter"></div>');
        }

        $fields.wrapAll('<div class="GFERowContainer clearfix groupDiv" />');

        var $rowContainer = $fieldSet.children('.GFERowContainer');

        if(!this.readOnly)
        {
          if (this.isCollapsable) {
              $fields.wrapAll('<div class="GFERowExpanded" />');
              var rowhandler = this.sortable ? '<span class="GFERowHandler hide"><img width="16" height="38" title="' + GlizyLocale.Repeater.drag + '" alt="' + GlizyLocale.Repeater.drag + '" src="./application/templates/images/dragHandler.gif"></span>' : '';
                $rowContainer
                    .append('<div class="GFERowCollapsed"><div class="GFERowHeader">'+rowhandler+'<div class="GFERowPreview"></div></div>'
                                +'<div class="GFERowPanel">'
                                    +'<div class="groupButton pull-right GFERowDelete"><i class="btn btn-danger btn-flat fa fa-times" aria-hidden="true"></i></div>'
                                    +'<div class="groupButton pull-right GFERowEdit"><i class="btn btn-info btn-flat fa fa-pencil" aria-hidden="true"></i></div>'
                                +'</div>')
                .children('.GFERowExpanded')
                    .append('<div class="GFERowButtonContainer"><input type="button" value="' + GlizyLocale.Repeater.confirm + '" class="btn btn-primary GFERowDoCollapse  GFERowDoConfirm">&nbsp;<input type="button" value="' + GlizyLocale.Repeater.cancel + '" class="btn GFERowDoCollapse"></div>')
                    .hide();
          }
          else {
              var rowhandler = this.sortable ? '<span class="GFERowHandler GFERowHandlerExpanded"><img width="16" style="height:100%" title="' + GlizyLocale.Repeater.drag + '" widalt="' + GlizyLocale.Repeater.drag + '" src="./application/templates/images/dragHandler.gif"></span>' : '';
              $rowContainer
                  .append(rowhandler + '<img width="16" height="16" class="icon GFERowDelete GFERightIcon" title="' + GlizyLocale.Repeater.remove + '" alt="' + GlizyLocale.Repeater.remove + '" src="./application/templates/images/icon_delete.gif">')
          }
        }

        $fieldSet.data('rowModel', $rowContainer.clone(true)
            .find('[name]').val('').removeAttr('id').end()
        );

        $rowContainer.remove();

        if (!this.noAddRowButton && !this.readOnly && !this.noEmptyMessage) {
            $fieldSet.prepend('<div class="GFEEmptyMessage">' + (this.minRec ? GlizyLocale.Repeater.noRecordEntered1 + this.minRec + GlizyLocale.Repeater.noRecordEntered2 : GlizyLocale.Repeater.clickAddRecordButton) + '</div>');
			  	$fieldSet.prepend('<div class="border-legend"></div>');
        }

        // TODO: aggiungere solo se non è stato aggiunto in precedenza
        $('body').append('<div class="GFETranslucentCover"></div>');

        this.makeSortable();

        var invalidFields = 0;
        var customValidationInvalid = false;

        $fieldSet.data('instance', self);

        jQuery(document).on('click', '.GFERowDoCollapse', function () {
            var $button = $(this),
                hasConfirmed = $button.hasClass('GFERowDoConfirm'),
                $rowCont = $button.closest('.GFERowContainer'),
                $inputFields = $('[name]', $rowCont),
                //$inputFields = $('input:not([type=button]), textarea', $rowCont),
                fieldPrev = '';
            var orId = self.originalId();
            // if (hasConfirmed && (form.triggerHandler('submitForm') === false && invalidFields || customValidationInvalid)) {
            //     customValidationInvalid = false;
            //     return;
            // }

            $rowCont.removeClass('GFEEditingRow').children('.GFERowCollapsed').show()
                .end().children('.GFERowExpanded').hide();

            // TODO rivedere questa parte, forse conviene usare this.children
            if (hasConfirmed) {
                $inputFields.each(function () {
                    var $this = $(this);
                    var obj = $this.data('instance');
                    if (obj) {
                        if( obj.$element[0].name.indexOf('group_ID') > -1)
                        {
                          fieldPrev = 'ID Gruppo: '+obj.$element[0].value;
                        }
                        var val = obj.getValue();
                        if (val) {
                            $this.data('oldVal', val);
                            //fieldPrev += getFieldPreview.call($this, val);
                        }
                    }
                });
                if(fieldPrev != '')
                {
                  $('.GFERowPreview', $rowCont).html(fieldPrev);
                }
            }
            else {
                var orId = $rowCont.data('originalId');

                self.setOldVal($inputFields);
                self.verifySelectWithTarget($rowCont);
                if (orId && orId.indexOf("new-") !== -1) {
                    $rowCont.remove();
                }
                $rowCont.removeClass('GFEEditingRow').children('.GFERowCollapsed').show()
                    .end().children('.GFERowExpanded').hide();
            }

            $('.GFETranslucentCover').hide();
        });

        $(document).on('click', '.GFERowEdit', function (e) {
            var $container = $(this).closest('.GFERowContainer'),
                $contBound = $container[0].getBoundingClientRect(),
                $window = $(window),
                wHeight = $window.height(),
                $button = $(e.currentTarget),
                $rowCont = $button.closest('.GFERowContainer'),
                $inputFields = $('[name]', $rowCont);
            
            self.registryOldVal($inputFields);

            $container.addClass('GFEEditingRow')
                .children('.GFERowCollapsed').hide()
                .end().children('.GFERowExpanded').show();
            
            $rowCont = $button.closest('.GFERowContainer');

            $window.scrollTop($container.offset().top - Math.max((wHeight - $container.height()) / 2, 0));

            $('.GFETranslucentCover').show();
            $('.GFETranslucentCover').css('background-color', 'none');
        });

        $('#'+addBtnId+'-addRowBtn').on('click', function () {
            var $button = $(this),
                $fieldSet = $button.parents('fieldset:first'),
                self = $fieldSet.data('instance'),
                $rows = $fieldSet.children('.GFERowContainer');

            if ($button.hasClass('GButtonDisabled')) {
                return;
            }

            if (self.maxRec && $rows.length == self.maxRec - 1) {
                $button.addClass('GButtonDisabled').attr('disabled', 'disabled').blur();
            }

            var newRowId;

            // Gestisce il caso di riordino dei GFERowContainer
            // oppure la cancellazione di un GFERowContainer tra più GFERowContainer
            if ($rows.length > 0) {
                // gli id dei GFERowContainer in un array
                var rowsId = $rows.map(function() { return $(this).data('id') }).get();
                newRowId = Math.max.apply(Math, rowsId) + 1;
            } else {
                newRowId = 0;
            }
            self.addRow($fieldSet, $button.closest('.GFEFooter'), newRowId, true);
            //self.makeSortable();
        });

        Glizy.events.on("glizycms.fileUpload", function(e) {
            var $footer = $fieldSet.children('.GFEFooter');
            var $rows = $fieldSet.children('.GFERowContainer');

            self.addRow($fieldSet, $footer, $rows.length, true);

            //$fieldSet.syncRecords().makeSortable();
            $fieldSet.find('.GFERowEdit:last').click();

            //self.verifySelectWithTarget($fieldSet);
            //enableValidation();

            var $title = $fieldSet.find('input[name*=title]:last');

            if ($title) {
                $title.val(e.message.fileName.replace(/\.[^/.]+$/, ""));
            }
        });
    },

    verifySelectWithTarget: function($container) {
        $container.find('select').each(function () {
            var target = $(this).data('target');
            if ( target ) {
                $(this).change(function(e){
                    var sel = this.selectedIndex,
                        name = this.name,
                        states = $(this).data("val_"+sel),
                        stateMap = {};
                    var t = target.split(",");
                    states = states.split(",");

                    $(t).each(function(index, val) {
                        stateMap[val] = states[index];
                    });

                    $container.find("[name]").each(function(){
                        var $el = $(this);
                        var state = stateMap[$el.attr("name")];
                        if (state === '1') {
                            $el.closest("div.control-group").show();
                        } else if (state == '0') {
                            $el.closest("div.control-group").hide();
                        }
                    });
                });
                $(this).trigger("change");
            }
        });
    },

    getOptions: function () {
        this.isCollapsable = this.$element.attr('data-collapsable') == 'true';
        this.minRec = parseInt(this.$element.attr('data-repeatmin') || 0);
        this.maxRec = parseInt(this.$element.attr('data-repeatmax') || 0);
        this.noAddRowButton = this.$element.attr('data-noAddRowButton') == 'true';
        this.sortable = this.$element.attr('data-sortable') == 'true' || this.$element.attr('data-sortable') === undefined;
        this.readOnly = this.$element.attr('data-readOnly') == 'true';
        this.noEmptyMessage = this.$element.attr('data-noEmptyMessage') == 'true';
        this.customAddRowLabel = this.$element.attr('data-customAddRowLabel');
    },

    addDeleteHandler : function(containerId) {
        var self = this;

        $('#'+containerId+' div.GFERowDelete').on('click', function () {
            var $container = $('#'+containerId),
                $fieldSet = $container.parent(),
                $rows = $fieldSet.children('.GFERowContainer');

            var id = $container.data('id');

            //overloadCaller.call($('#fileuploader'), 'removeFile', i);

            if ($rows.length == 0) {
                //alert(GlizyLocale.Repeater.minRecordMsg + self.minRec);
                return;
            }

            for (var field in self.children[id]) {
                var fieldObj = self.children[id][field];
                fieldObj.destroy();
            }

            self.children.splice(id, 1);

            $('.GFEAddRow').removeClass('GButtonDisabled').removeAttr('disabled');
            $container.remove();

            if (!$fieldSet.children('.GFERowContainer').length) {
                $('.GFEEmptyMessage:first', $fieldSet).show();
            }
        });
    },

    addRow: function (fieldSet, footer, id, justCreated, noVerifySelectWithTarget) {
        var idParentPrefix = (this.idParent === null) ? '' : this.idParent+'-';
        var self = this;
        var fieldSetId = fieldSet.attr('id');
        var containerId = idParentPrefix+fieldSetId+id;
        var $container = fieldSet.data('rowModel').clone(true);
        $container.find('.GFERecordId').text(id + 1);
        $container.attr('id', containerId);

        footer.before($container);

        $('#'+containerId).data('justCreated', justCreated || false);
        $('#'+containerId).data('id', id);

        this.addDeleteHandler(containerId);

        $('.GFEEmptyMessage:first', fieldSet).hide();

        this.children.splice(id, 0, {});

        $('#'+containerId+' input[name]:not( [type="button"], [type="submit"], [type="reset"] ), '+
          '#'+containerId+' select[name], '+
          '#'+containerId+' textarea[name], '+
          '#'+containerId+' fieldset[data-type]').each(function () {
            var element = $(this);

            var parents = element.parents('[data-type]');

            // se l'elemento è contenuto immediatamente nel repeater
            if (parents[0] == self.$element[0]) {
                var addBtnId = containerId+'-'+$(this).attr('id');
                self.createChild(id, element, addBtnId, containerId);
            }
        });

        if (noVerifySelectWithTarget === undefined) {
            this.verifySelectWithTarget($container);
        }
        return $container;
    },

    createChild: function(rowId, element, addBtnId, containerId) {
        var type = element.data('type') || 'standard';
        var obj = Glizy.oop.create("glizy.FormEdit."+type, element, this.glizyOpt, this.$form, addBtnId, containerId);
        var name = obj.getName();
        this.children[rowId][name] = obj;
    },

    makeSortable: function () {
        var from;
        var self = this;
        return this.$element.sortable({
            items: '.GFERowContainer',
            handle: '.GFERowHandler',
            start: function (ev, ui) {
                from = ui.item.index()-2;
            },
            stop: function (ev, ui) {
                var to = ui.item.index()-2;

                // sposta un elemento nell'array da from a to
                function arraymove(arr, from, to) {
                    var element = arr[from];
                    arr.splice(from, 1);
                    arr.splice(to, 0, element);
                }

                if (from !== to) {
                    arraymove(self.children, from, to);
                }
            }
        });
    },

    getValue: function () {
        var data = [];
        for (var i in this.children) {
            var row = this.children[i];
            var obj = {};
            for (var field in row) {
                var fieldObj = row[field];

                if (!fieldObj.isDisabled()) {
                    var val = fieldObj.getValue();
                    obj[field] = val;
                }
            }
            data.push(obj);
        }

        return data;
    },

    setValue: function (value) {
        if (value && value.length > 0) {
            var $fieldSet = this.$element;
            var $footer = $fieldSet.children('.GFEFooter');

            for (var i in value) {
                var $container = this.addRow($fieldSet, $footer, i, true, true);
                var row = value[i];
                var groupID = row[Object.keys(row)[0]];
                $container.find(".GFERowPreview").html('ID Gruppo: '+groupID);
                for (var field in row) {
                    var v = row[field];
                    var obj = this.children[i][field];

                    if (obj) {
                        obj.setValue(v);
                    }
                }
                this.verifySelectWithTarget($container);
            }
        }
    },

    getName: function () {
        return this.id;
    },

    focus: function() {
        $('html, body').animate({ scrollTop: this.$element.offset().top - this.$element.prop('scrollHeight')}, 'slow');
    },

    isValid: function() {
        if (this.minRec == 0 || this.children.length >= this.minRec) {
            var isValid = true;
            for (var i in this.children) {
                var row = this.children[i];
                for (var field in row) {
                    var fieldObj = row[field];

                    if (!fieldObj.isValid()) {
                        fieldObj.addClass('GFEValidationError');
                        fieldObj.getElement().parents('.GFERowContainer').addClass('GFEValidationError');
                        isValid = false;
                    } else {
                        fieldObj.removeClass('GFEValidationError');
                        if(isValid)
                        {
                          fieldObj.getElement().parents('.GFERowContainer').removeClass('GFEValidationError');
                        }
                    }
                }
            }
            return isValid;
        } else {
            return false;
        }
    },

    registryOldVal: function (fields) {
        fields.each(function () {
            var $this = $(this);
            var obj = $this.data('instance');
            if (obj) {
                var val = obj.getValue();
                if (val) {
                    $this.data('oldVal', val);
                }
            }
        });
    },

    setOldVal: function (fields) {
        fields.each(function () {
            var $this = $(this);
            var obj = $this.data('instance');

            if (obj) {
                var val = obj.setValue($(this).data('oldVal') || '');
            }

            if (!$this.data('overloadCalled')) {
                $this.val($this.data('oldVal') || '');
            }
            $this.removeClass('GFEValidationError');
        });
    }
});
