Glizy.oop.declare("glizy.FormEdit.conditionalSelectFrom", {
    $extends: Glizy.oop.get('glizy.FormEdit.selectfrom'),
    target: null,
    targetPool: [],
    elementId: "",

    arrayUnique: function(array) {
        var a = array.concat();
        for(var i=0; i<a.length; ++i) {
            for(var j=i+1; j<a.length; ++j) {
                if(a[i] === a[j])
                    a.splice(j--, 1);
            }
        }

        return a;
    },

    setValue: function (value) {
        this.$super(value);
        this.$element.trigger("change");
    },

    hideComponent: function (rootId, componentId) {
        var dis = rootId ? $("#"+rootId+',[name="'+rootId+'"]') : $(this);
        var ceiling = dis.closest('div.form-group');
        var $compId = ceiling.siblings().parent().find('#'+componentId+',[name="'+componentId+'"]');
        $compId.hide();
        $compId.closest('div.form-group').hide();
    },

    showComponent: function (rootId, componentId) {
        var dis = rootId ? $("#"+rootId+',[name="'+rootId+'"]') : $(this);
        var ceiling = dis.closest('div.form-group');
        var $compId = ceiling.siblings().parent().find('#'+componentId+',[name="'+componentId+'"]');
        $compId.show();
        $compId.closest('div.form-group').show();
    },

    /**
     * Formato singolo item di regex:
     *
     * <VALORI>`--><LISTA>
     *
     *
     * <VALORI> è tipo "##valore1## ##valore2##" oppure "*"
     * Significa che se uno dei valori delineati è scelto o qualsiasi (se si usa *), allora si attivano i campi in <LISTA>
     *
     * <LISTA> è tipo "campo1,campo2,campo3"
     * Significa che saranno questi gli id dei campi da attivare
     */
    targetItemRegex: "^((##[^=#]+##)( ##[^=,#]+##)*|\*)`-->([^,]+((,[^,]+)+))$",
    targetProcessing: function(targetString){
        this.target = [];
        var targItems = targetString.split("|_|").map(function(a){return a.hasOwnProperty("trim") ? a.trim() : a;}).filter(function(a){return a.match(this.targetItemRegex) !== null;});

        for (i in targItems){
            var item = targItems[i];
            var values = (item.split("`-->")[0]).trim().split("##").map(function(a){return a.hasOwnProperty("trim") ? a.trim() : a;}).filter(function(a){return a.hasOwnProperty("trim") ? a.trim() : a;});
            var fields = (item.split("`-->")[1]).trim().split(",").map(function(a){return a.hasOwnProperty("trim") ? a.trim() : a;}).filter(function(a){return a.hasOwnProperty("trim") ? a.trim() : a;});

            for (v in values){
                var value = values[v];
                var cur = this.target[value];
                this.target[value] = this.arrayUnique(fields.concat(Array.isArray(cur) ? cur : []));
            }
            this.targetPool = this.arrayUnique(fields.concat(Array.isArray(this.targetPool) ? this.targetPool : []));
        }
    },

    initialize: function (element) {
        this.$super(element);
        this.elementId = element.prop("id") || element.prop("name") || "body";
        this.targetProcessing(element.data('target'));
        var hideComponent = this.hideComponent.bind(this, this.elementId);
        var showComponent = this.showComponent.bind(this, this.elementId);

        var changedSelection = function(){
            this.targetPool.forEach(hideComponent);

            var selectedVal = this.getValue().trim();
            if (selectedVal && Array.isArray(this.target["*"])){
                this.target["*"].forEach(showComponent);
            } else if (selectedVal && Array.isArray(this.target[selectedVal])){
                this.target[selectedVal].forEach(showComponent);
            }
        }.bind(this);

        element.on('change', changedSelection);
        element.trigger('change');
    }
});