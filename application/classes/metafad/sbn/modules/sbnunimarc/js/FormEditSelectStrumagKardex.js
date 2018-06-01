Glizy.oop.declare("glizy.FormEdit.selectStruMagKardex", {
    $extends: Glizy.oop.get('glizy.FormEdit.selectfrom'),

    initialize: function (element) {
        element.data('instance', this);
        this.$element = element;

        element.removeAttr('value');
        element.css('width', '500px');

        var fieldName = element.data('field') || element.attr('name');
        this.multiple = element.data('multiple');
        var addNewValues = element.data('add_new_values');
        var model = element.data('model');
        var query = element.data('query');
        var proxy = element.data('proxy');
        var proxyParams = element.data('proxy_params');
        if (proxyParams) {
            proxyParams = proxyParams.replace(/##/g,'"');
        }
        var placeholder = element.data('placeholder');
        var originalName = element.data('originalName');
        var getId = element.data('get_id');
        var selectedCallback = element.data('selected_callback');
    	var minimumInputLength = element.data('min_input_length') || 0;
    	var formatSelection = element.data('format_selection');
        var formatResult = element.data('format_result');

        var detachCallback = element.data('detach_callback');

        if (originalName !== undefined && element.data('override')!==false) {
            fieldName = originalName;
        }

        element.select2({
        	width: 'off',
            multiple: this.multiple,
            minimumInputLength: minimumInputLength,
            placeholder: placeholder === undefined ? '' : placeholder,
            allowClear: true,
            ajax: {
                url: Glizy.ajaxUrl + "&controllerName=org.glizycms.contents.controllers.autocomplete.ajax.FindTerm",
                dataType: 'json',
                quietMillis: 250,
                data: function(term, page) {
                    return {
                        fieldName: fieldName,
                        model: model,
                        query: query,
                        term: term,
                        proxy: proxy,
                        proxyParams: proxyParams,
                        getId: getId
                    };
                },
                results: function(data, page ) {
                    return { results: data.result }
                }
            },
            createSearchChoice: function(term, data) {
                if (!addNewValues) {
                    return false;
                }

                if ($(data).filter(function() {
                    return this.text.localeCompare(term)===0;
                }).length===0) {
                    return {id:term, text:term};
                }
            },
            formatResult: function(data) {
                return formatResult === undefined ? data.text : window[formatResult](data);
            },
            formatSelection: function(data) {
                if (selectedCallback && !data.noCallBack) {
                    var term = data.text;

                    $.ajax({
                        url: Glizy.ajaxUrl+"&controllerName="+selectedCallback,
                        data: {
                            fieldName: fieldName,
                            model: model,
                            query: query,
                            term: term,
                            proxy: proxy,
                            proxyParams: proxyParams,
                            getId: getId,
                            data: data
                        },
                        type: "POST"
                    });
                }

                return formatSelection === undefined ? data.text : window[formatSelection](data);
            },
            formatNoMatches: function () { return GlizyLocale.selectfrom.formatNoMatches; },
            formatSearching: function () { return GlizyLocale.selectfrom.formatSearching; }
        });
        
        element.on('change', function(e) {
            if (e.val == '') {
                $.ajax({
                    url: Glizy.ajaxUrl+"&controllerName="+detachCallback,
                    data: {
                        proxyParams: proxyParams,
                    },
                    type: "POST"
                });
            }
        });

        if (this.multiple) {
            element.parent().find("ul.select2-choices").sortable({
                containment: 'parent',
                start: function() { element.select2("onSortStart"); },
                update: function() { element.select2("onSortEnd"); }
            });
        }

    },
});