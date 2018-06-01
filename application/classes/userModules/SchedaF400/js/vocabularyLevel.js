$(document).ready(function(){
    Glizy.events.on("glizycms.formEdit.onReady", function(){
        $.each([['PVCS','PVCR'],['PVCR','PVCP'],['PVCP','PVCC'],['PVCC','PVCL'],['LDCT','LDCQ'],['PRVS','PRVR'],['PRVR','PRVP'],['PRVP','PRVC'],['PRVC','PRVL'],['PRCT','PRCQ'],['INPZ','INPS'],['LRCS','LRCR'],['LRCR','LRCP'],['LRCP','LRCC'],['LRCC','LRCL'],['ADSP','ADSM']], function(i, item){
            if ($('#'+item[0]).val()) {
                initializeSelectAfterParent($('#'+item[0]), item[1]);
            }

            $('input[name='+item[0]+']').on('change', function(){
                initializeSelectAfterParent($(this), item[1]);
            });
        });
    });

    function initializeSelectAfterParent(el,name)
    {
        if (el.select2('data')) {
            var parentKey = el.select2('data').id;
            var rsec = el.closest('fieldset').find('input[name='+name+']');
            var proxyParams = rsec.data('proxy_params');
            proxyParams = proxyParams.replace("##}","##,##parentKey##:##"+parentKey+"##}");
            var instance = rsec.data('instance');
            var value = instance.getValue();
            instance.$element.data('proxy_params',proxyParams);
            instance.initialize(instance.$element);
            if (value) {
                instance.setValue(value);
            }
        }
    }
});