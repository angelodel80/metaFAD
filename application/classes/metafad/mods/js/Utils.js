function meta_translateToSigla(mod) {
    var pref = 'archivi.models.';
    if (mod == pref + "ComplessoArchivistico") return "CA";
    else if (mod == pref + "UnitaDocumentaria") return "UD";
    else if (mod == pref + "UnitaArchivistica") return "UA";
    else if (mod == pref + "ProduttoreConservatore") return "ENT";
    else if (mod == pref + "SchedaStrumentoRicerca") return "SR";
    else if (mod == pref + "SchedaBibliografica") return "SB";
    else if (mod == pref + "FonteArchivistica") return "FA";
    return "XX";
}

$(document).ready(function () {
    /**
     * SEZIONE HANDLER PER LA DATA
     */
    /**
     * Handler per il cambio del picker secolo
     */
    function formatDateText(elem) {
        var mdu = new MetaDateUtils();
        elem = (elem instanceof jQuery) ? elem : $(this);

        //Prendo la data dal txt e la parso
        var curDate = elem.prop('value');
        var dateObj = mdu.parseDate(curDate);

        //Avverto in caso di errore
        if (dateObj.error && curDate && curDate.length > 0) {
            alert(dateObj.error);
            console.log(dateObj.error);
        }

        //Preparo la formattazione
        curDate = [dateObj.year ? dateObj.year : false, dateObj.month ? dateObj.month : false, dateObj.day ? dateObj.day : false].filter(function (a) {
            return !!a;
        });

        //Inserisco la data formattata, restituisco l'oggetto parsato e l'elemento del DOM
        elem.prop('value', curDate.join("/"));
        return {elem: elem, dateObj: dateObj};
    }

    /**
     * Handler per la onChange degli input data.
     */
    var inputOnDateChange = function () {
        //Parso la data a partire dalla textbox DOM
        formatDateText($(this));
    };

    /*Per le cronologie "statiche", ovvero già presenti nella pagina senza azioni successive*/
    $('input[class*="js-archive-cronologia-dateInput"]').on('focusout', inputOnDateChange);
    $('input[class*="js-archive-dateTextBox"]').on('focusout', inputOnDateChange);

    /*Per le cronologie "dinamiche". Si suppone che l'id generato per l'intera cronologia dinamica
     sia "undefined-cronologia..."*/
    $('input[id$="addRowBtn"]').filter('[type="button"]').on('click', function () {
        var x = $('input[class*="js-archive-cronologia-dateInput"]');
        x.unbind('focusout', inputOnDateChange); //Unbind dell'handler, se già presente.
        x.on('focusout', inputOnDateChange);
    });

    /**
     * SEZIONE PER LE MODALI
     */
    $('.deleteSelected').attr('data-toggle', "modal").attr('data-target', "#myModalConfirm");

    $('.annulla').click(function () {
    });

    setTimeout(function () {
        var id = $('#identificazione');
        id.addClass('active');
        id.addClass('tab-pane');
        $('.dropdown').addClass('active');
        $('.btn[data-toggle = "tab-next"]').addClass('btn');
        $('#dataGrid_filter input').addClass('form-control').attr('placeholder', 'Filtra elenco...').val('');
        $('#dataGridDetail_filter input').addClass('form-control').attr('placeholder', 'Filtra elenco...').val('');
        $('.buttonDelete.btn.btn-flat.btn-danger.GFERowDelete').text('Cancella scheda');

    }, 0);

    $('.wrapper').attr('id', 'archivi-wrapper');

    var $aCancel = $('a[title="Cancella"]');
    $aCancel.attr('data-toggle', "modal").attr('data-target', "#myModalConfirm");
    $aCancel.attr('onclick', "$('.ok').click( function(){ location.href='" + $aCancel.attr('href') + "'; $('#archivi-wrapper').attr('class', 'wrapper disabled');});");
    $aCancel.removeAttr('href');


    /**
     * TODO: QUICK-FIXES
     */

    /*
     ...
     */
    var $table = $("table.dataTable[id^='dataGrid']");
    if ($table.length > 0) {
        $table.dataTable()
    }

    /**
     * MFADEV-190
     */
    $('.js-ecommerce').parents('.form-group').addClass('form-group-ecommerce');
});
