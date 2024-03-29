$(function(){
    // TODO: localizzare stringhe
    var progressPart, progress, steps, stepPos, stepResult, ajaxUrl;

    function openExportDialog() {
        window.onbeforeunload = exitWarning;
        $( "#progress_bar" ).modal({ overlayCss: {background: "#000"}, overlayClose: false, closeHTML:'' });
    }

    function closeExportDialog(callback) {
        window.onbeforeunload = null;
        $( "#progress_bar" ).modal('toggle');
        resetProgressBar();
        callback();
    }

    function exitWarning(e) {
        var msg = 'Attenzione, uscendo da questa pagina verra\' interrotto il processo!';
        e = e || window.event;
        // For IE and Firefox prior to version 4
        if (e) {
          e.returnValue = msg;
        }
        // For Safari
        return msg;
    };

    function resetProgressBar() {
        jQuery.fx.off = true;
        $('#progress_bar .ui-progress').width("0%");
        $('#progress_bar .ui-label').hide();
    };

    function execStep(callback) {
        progress += progressPart;
        $('#progress_bar .ui-progress').animateProgress( progress );

        if ( stepPos == steps.length-1 )
        {
            closeExportDialog(callback);
            if (steps[ stepPos ].url) {
                location.href = steps[ stepPos ].url;
            } else  if (steps[ stepPos ].message) {
                alert(steps[ stepPos ].message);
            }
            return;
        }

        // per ogni azione esegue una richiesta ajax
        jQuery.ajax( {
            url: ajaxUrl+steps[ stepPos ].action,
            data: steps[ stepPos ].params,
            dataType: "json",
            success: function( data ){
                stepResult = data;
                stepPos++;
                execStep(callback);
            } } );

    }

    Glizy.startAjaxSteps = function(data, callback, getSteps) {
        ajaxUrl = $('.js-glizycms-FormEditWithAjaxSteps').data('ajaxurl');
        openExportDialog();
        getSteps = getSteps || 'getSteps';

        $.ajax({
            url: ajaxUrl+getSteps,
            data: data,
            dataType: 'json',
            success: function( data ) {
                if ( data.status)
                {
                    if (data.result.length) {
                        progressPart = 100 / (data.result.length - 1);
                        progress = 0;
                        stepPos = 0;
                        steps = data.result;
                        execStep(callback);
                    } else {
                        closeExportDialog(callback);
                    }
                }
                else
                {
                    closeExportDialog(callback);
                    alert( 'Si è verificato un errore' );
                }
            }
        });
    };
});