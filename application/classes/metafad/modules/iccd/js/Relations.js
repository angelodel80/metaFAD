$(document).ready(function(){
    $('input[name=RSET]').on('change', function() {
        var rsec = $(this).closest('.GFERowContainer').find('input[name=RSEC]');
        var instance = rsec.data('instance');
        instance.$element.data('proxy_params','{##iccdModuleType##:##' + $(this).val() + '##}');
        instance.initialize(instance.$element);
    });
});
