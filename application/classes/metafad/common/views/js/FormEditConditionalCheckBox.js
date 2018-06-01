Glizy.oop.declare("glizy.FormEdit.conditionalCheckBox", {
    $extends: Glizy.oop.get('glizy.FormEdit.checkbox'),
    target: null,

    initialize: function (element) {
        this.$super(element);

        var that = this;
        that.target = element.data('target').split(",");

        var i;
        for(i = 0; i < that.target.length; i++){
            that.hideComponent(that.target[i]);
        }

        element.on('ifChanged change', function(){
           for(i = 0; i < that.target.length; i++){
                var a = that.target[i];
                if (that.getValue()){
                    that.showComponent(a);
                } else {
                    that.hideComponent(a);
                }
            }
        });

        element.trigger('change');
    },

    hideComponent: function (componentId) {
        var $compId = $('#'+componentId);
        $compId.hide();
        $compId.parents('div.form-group').hide();
    },

    showComponent: function (componentId) {
        var $compId = $('#'+componentId);
        $compId.show();
        $compId.parents('div.form-group').show();
    }
});