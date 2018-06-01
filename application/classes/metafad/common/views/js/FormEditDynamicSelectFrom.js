Glizy.oop.declare("glizy.FormEdit.dynamicselectfrom", {
    $extends: Glizy.oop.get('glizy.FormEdit.selectfrom'),
    multiple: null,
    model: 'Archivi.models.Model',
    controller: 'archivi.controllers.ajax.GetFieldValueFromId',
    field: '_denominazione',


    initialize: function (element) {
        this.model = element.data('model');
        this.controller = element.data('controller');
        this.field = element.data('textfield');

        this.$super(element);
    },

    setValue: function (value) {
        var that = this;
        if (!this.multiple) {
            if (value) {
                if (typeof(value)=="object") {
                    $.ajax({
                        url: Glizy.ajaxUrl+"&controllerName="+this.controller,
                        data: {
                            textfield: that.field,
                            model: that.model,
                            id: value.id
                        },
                        type: "GET",
                        success: function (response, status, xhr){
                            var name = response.result ? response.result.result : "";
                            that.$element.select2('data', {id: value.id, text: name});
                        }
                    });
                } else {
                    this.$element.select2('data', {id: value, text: value});
                }
            }
        }
        else if (Array.isArray(value)) {
            var arrayVal = [];

            $.each(value, function(index, v) {
                if (typeof(v)=="object") {
                    arrayVal.push(v);
                }
                else {
                    arrayVal.push({id: v, text: v});
                }
            });

            this.$element.select2('data', arrayVal);
        }
    }
});