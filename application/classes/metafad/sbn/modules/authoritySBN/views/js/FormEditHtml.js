Glizy.oop.declare("html", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    
    initialize: function (element) {
        element.data('instance', this);
        element.hide();
        this.$element = element;
    },
    
    setValue: function (value) {
        this.$element.parent().html(value);
    },
});