Glizy.oop.declare("glizy.FormEdit.FormEditMandatory", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    
    initialize: function(formId, glizyOpt) {
        this.$super(formId, glizyOpt);
    
        if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-alternative\-mandatory/) > -1)
            $(this.$element).closest('.form-group').find('label').addClass('mandatory-element-alternative');
        else if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-context\-mandatory/) > -1)
            $(this.$element).closest('.form-group').find('label').addClass('mandatory-element-context');
        else if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-onlyOne\-mandatory/) > -1)
            $(this.$element).closest('.form-group').find('label').addClass('mandatory-element-onlyOne');
    },

    isValid: function() {
        var currentClasses = $(this.$element).prop('class');
        var classes = currentClasses.split(' ');
        var group = '';
        for (var i = 0; i < classes.length; i++)
            if (classes[i].search(/([A-Za-z0-9]+)\-(alternative|context|onlyOne)\-mandatory/) > -1) {
                group = classes[i];
                break;
            }

        if (group != '') {
            var mp = group.match(/([A-Za-z0-9]+)\-(alternative|context|onlyOne)\-mandatory/);
            var groupName = mp[1];
            var mandatoryType = mp[2];
            
            var parent = $(this.$element).closest('.GFEFieldset');
            if ($(parent).length <= 0)
                parent = $(this.$element).closest('.tab-pane');
            if ($(parent).length <= 0)
                parent = $(this.$element).closest('fieldset');
            
            var groupElements = $(parent).find('input[class*="' + group + '"]');
            var counter = 0;
            for (var i = 0; i < groupElements.length; i++)
                if ($(groupElements[i]).val() != '')
                    counter++;
            
            if (mandatoryType == 'alternative') {
                //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter > 0 && groupElements.length > 0));
                return (counter > 0 && groupElements.length > 0);
            } else if (mandatoryType == 'context') {
                //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter == groupElements.length || counter == 0));
                return (counter == groupElements.length || counter == 0);
            } else if (mandatoryType == 'onlyOne') {
                //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter == groupFieldsets.length || counter == 0));
                return (counter == 1 || groupElements.length  == 0);
            }
        }
        
        return true;
    }
});