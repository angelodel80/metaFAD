Glizy.oop.declare("glizy.FormEdit.FormEditRepeatMandatory", {
    $extends: Glizy.oop.get('glizy.FormEdit.repeat'),
    
    initialize: function (element, glizyOpt, form, addBtnId, idParent) {
        this.$super(element, glizyOpt, form, addBtnId, idParent);
        
        if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-alternative\-mandatory/) > -1)
            $(this.$element).find('legend').addClass('mandatory-element-alternative');
        else if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-context\-mandatory/) > -1)
            $(this.$element).find('legend').addClass('mandatory-element-context');
        else if ($(this.$element).prop('class').search(/([A-Za-z0-9]+)\-onlyOne\-mandatory/) > -1)
            $(this.$element).find('legend').addClass('mandatory-element-onlyOne');
    },
    
    addRow: function (fieldSet, footer, id, justCreated, noVerifySelectWithTarget) {
        var parentContainer = this.$super(fieldSet, footer, id, justCreated, noVerifySelectWithTarget);
        
        return $(parentContainer).prepend('<p><i>' + $(fieldSet).children('legend').html() + '</i></p>');
    },

    isValid: function() {
        var isValid = this.$super();
        
        if (isValid) {
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
            
                var parent = $(this.$element).parent().closest('fieldset');
            
                var groupFieldsets = $(parent).find('fieldset[class*="' + group + '"]');
                var counter = 0;
                for (var i = 0; i < groupFieldsets.length; i++) {
                    var fs = groupFieldsets[i];
                    var rows = $(fs).find('.GFERowContainer');
                    
                    if (rows.length)
                        counter++;
                
                    /* Il codice commentato permette di verificare i valori dei campi presenti all'interno dei fieldset repeater */
                    /*var fs = groupFieldsets[i];
                    var inputs = $(fs).find('input[class*="form-control"]');
                    
                    if (inputs.length)                    
                        for (var j = 0; j < inputs.length; j++)
                            if ($(inputs[j]).val() != '') {
                                counter++
                                break;
                            }
                    */
                }

                if (mandatoryType == 'alternative') {
                    //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter > 0 && groupFieldsets.length > 0));
                    return (counter > 0 && groupFieldsets.length > 0);
                } else if (mandatoryType == 'context') {
                    //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter == groupFieldsets.length || counter == 0));
                    return (counter == groupFieldsets.length || counter == 0);
                } else if (mandatoryType == 'onlyOne') {
                    //console.log('Mandatory: ' + mandatoryType + ' Group: ' + groupName + ' Result: ' + (counter == groupFieldsets.length || counter == 0));
                    return (counter == 1 || groupFieldsets.length  == 0);
                }
            } else
                return true;
        }
        
        return true;
    }
});
