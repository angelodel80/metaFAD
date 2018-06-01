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

    function normalizeDate(elem, mdu, dateObj) {
        //Salgo al fieldset dell'estremo attuale
        var parent = elem.closest('fieldset[class*="js-archive-cronologia-estremo"]');

        //Prendo il secolo selezionato, se c'è
        var century = parent.find("div.js-archive-cronologia-century a span");
        var centuryVal = century.text();

        //Vado alla textbox della codifica e la setto con la data correttamente normalizzata
        var dateEncoding = parent.find('input[class*="js-archive-cronologia-codificaText"]');
        dateEncoding.prop('value', mdu.encodeDate(dateObj) || mdu.encodeCentury(centuryVal));

        //Radice del DOM degli estremi cronologici
        return parent.parent();
    }

    function generateEstremoCronologicoTestuale(rootDate, mdu) {
        //Estraggo i valori contenuti nell'input dei due estremi
        var estremoRecente = rootDate.find('fieldset[class*="js-archive-cronologia-recenteRoot"] input[class*="js-archive-cronologia-dateInput"]').prop('value');
        var estremoRemoto = rootDate.find('fieldset[class*="js-archive-cronologia-remotoRoot"] input[class*="js-archive-cronologia-dateInput"]').prop('value');

        //Parso i due valori dei due estremi
        var dataRemota = mdu.parseDate(estremoRemoto);
        var dataRecente = mdu.parseDate(estremoRecente);

        //Estraggo le due validità
        var validitaRecente = rootDate.find('fieldset[class*="js-archive-cronologia-recenteRoot"] div.form-group .js-archive-cronologia-valid-data span').text();
        var validitaRemota = rootDate.find('fieldset[class*="js-archive-cronologia-remotoRoot"] div.form-group .js-archive-cronologia-valid-data span').text();

        //Estraggo la qualifica data
        var qualificaData = rootDate.find('.js-archive-cronologia-qualifica-data span').text();

        //Cerco l'input dell'estremo cronologico testuale
        var estremoTestuale = rootDate.find('[class*="js-archive-cronologia-concatText"]');
        var estremoTxt = mdu.modifyByValidita(mdu.encodeDateYMD(dataRemota), validitaRemota, mdu.encodeDateYMD(dataRecente), validitaRecente);

        //Imposto il nuovo valore
        estremoTestuale.prop('value', mdu.modifyByQualifica(estremoTxt, qualificaData));
    }

    function generateCodificaTestuale(elem, mdu, dateObj) {
        //Codifico la data e restituisco la radice del blocco cronologia
        var rootDate = normalizeDate(elem, mdu, dateObj);

        //Genero il nuovo estremo cronologico testuale
        generateEstremoCronologicoTestuale(rootDate, mdu);
    }

    /**
     * Handler per la onChange degli input data.
     */
    var inputOnDateChange = function () {
        //Inizializzo l'utilities della data
        var mdu = new MetaDateUtils();

        //Parso la data a partire dalla textbox DOM
        var __ret = formatDateText($(this));
        var elem = __ret.elem;
        var dateObj = __ret.dateObj;

        generateCodificaTestuale(elem, mdu, dateObj);
    };

    /*Per le cronologie "statiche", ovvero già presenti nella pagina senza azioni successive*/
    $(document).on('focusout', 'input[class*="js-archive-cronologia-dateInput"]', inputOnDateChange);
    $(document).on('focusout', 'input[class*="js-archive-dateTextBox"]', function () {
        formatDateText();
    });

    /*
     Estremi cronologici + POLODEBUG-313
     */
    setInterval(function () { //Se la data è popolata, nascondi e svuota il secolo
        $(".js-archive-cronologia-dateInput").each(function () {
            var mdu = new MetaDateUtils();
            var data = $(this).prop("value");
            var estremo = $(this).closest('fieldset[class*="js-archive-cronologia-estremo"]');
            var century = estremo.find('div.js-archive-cronologia-century');
            var closeCentury = century.find('.select2-search-choice-close');

            if ((data + "").length > 0 /*&& secolo && secolo.text().length > 0*/) {
                //Commentato perché dava problemi con la navigazione tra tabs
                // closeCentury.trigger("mousedown");
                century.closest("div.form-group").hide();
            } else {
                century.closest("div.form-group").show();
            }

            generateCodificaTestuale($(this), mdu, mdu.parseDate(data || ""));
        });
        var x = $('input[class*="js-archive-dateTextBox"]');
        x.unbind('focusout', formatDateText); //Unbind dell'handler, se già presente.
        x.on('focusout', formatDateText);
    }, 300);
    //$(".select2-search-choice-close").trigger("mousedown");

    setInterval(function() { //Se il secolo è popolato, nascondi e svuota la data
        $('div.js-archive-cronologia-century').each(function() {
            var mdu = new MetaDateUtils();
            var century = $(this);
            var estremo = century.closest('fieldset[class*="js-archive-cronologia-estremo"]');
            var dateInput = estremo.find('.js-archive-cronologia-dateInput');
            var data = dateInput.prop("value");
            var secolo = century.find('a span');

            if (/*(data + "").length > 0 && */secolo && secolo.text().length > 0) {
                dateInput.prop("value", "");
                dateInput.closest("div.form-group").hide();
            } else {
                dateInput.closest("div.form-group").show();
            }

            generateCodificaTestuale($(this), mdu, mdu.parseDate(data || ""));
        })
    }, 300);


    /**
     * SEZIONE HANDLER PER L'IDENTIFICATIVO DI SISTEMA
     */
    function setIdentificativo() {
        var elem = $('#acronimoSistema');
        var acro = elem.prop('value');
        var id = $('#__id').val();
        var model = $('#__model').val();

        if (id == 0)
            return;

        $('#codiceIdentificativoSistema').prop('value', id);
        $('#identificativo').prop('value', acro + " " + meta_translateToSigla(model) + " " + id);
    }

    var $acronimoSistema = $('#acronimoSistema');
    $acronimoSistema.on('keyup', setIdentificativo);
    $acronimoSistema.on('focusin', setIdentificativo);
    $acronimoSistema.on('focusout', setIdentificativo);
    $('[class*="js-glizycms-save"]').on('click', setIdentificativo);


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
     * SEZIONE DELL'ALBERO GERARCHICO
     */
    $('.openTree').removeAttr('href');

    var $tree = $('.tree');
    $tree.click(function () {
        var $this = $('.tree');
        var $txtTab = $('.text-tab');
        var $gerarchia = $('.gerarchia');

        if ($this.hasClass('openTree')) {
            $this.removeClass('openTree');
            $this.addClass('closeTree');
            $gerarchia.hide();
            $txtTab.addClass('col-md-12');
            $txtTab.removeClass('col-md-8');
        } else {
            $this.addClass('openTree');
            $this.removeClass('closeTree');
            $gerarchia.show();
            $txtTab.removeClass('col-md-12');
            $txtTab.addClass('col-md-8');
        }
    });

    if ($tree.length == 0) {
        var $txtTab = $('.text-tab');
        $txtTab.addClass('col-md-12');
        $txtTab.removeClass('col-md-8');
    }


    /**
     * TODO: QUICK-FIXES
     */
    /*
     POLODEBUG-269 e 372 e POLODEBUG-310 (fatta a interval perché Cerullo vuole pure che l'utente possa modificarla)
     */

    var currentDate = new Date();
    var concatProvider = new MetaConcatUtils();
    var consistenze = [
        {
            'type': 'ca',
            'lastCons': concatProvider.generateConsistenza('ca'),
            'resultDest' : "input#consistenzaTotale"
        },
        {
            'type': 'ua',
            'lastCons': concatProvider.generateConsistenza('ua'),
            'resultDest' : "#visualizzazioneConsistenza"
        },
        {
            'type': 'ud',
            'lastCons': concatProvider.generateConsistenza('ud'),
            'resultDest' : "#visualizzazioneConsistenza"
        }
    ];
    setInterval(function () {
        //POLODEBUG-269
        $("div.select2-container + input[data-type='selectfrom'][data-proxy][data-proxy_params][data-dictid]").hide();

        //POLODEBUG-372
        $(".js-archive-dateCompilazione").each(function () {
            var txt = $(this).prop('value');
            var d = currentDate;
            if (!txt) {
                $(this).prop('value', [d.getFullYear(), ("0" + (d.getMonth() + 1)).slice(-2), ("0" + d.getDate()).slice(-2)].filter(function (a) {
                    return a;
                }).join("/"));
            }
        });

        //POLODEBUG-310 e 402, solo nel caso in cui cambiano i campi interni si avrà un'autogenerazione (così l'utente cambia quando vuole la consistenza)
        consistenze.map(function(tipo){
            var thisCons = concatProvider.generateConsistenza(tipo['type']);
            if (thisCons !== tipo['lastCons']){
                $(tipo['resultDest']).val(thisCons);
                tipo['lastCons'] = thisCons;
            }
            return tipo;
        });
    }, 500);


    /*
     ...
     */
    var $table = $("table.dataTable[id^='dataGrid']");
    if ($table.length > 0) {
        $table.dataTable()
    }
    /*
     POLODEBUG-270
     */
    /*var $gerarchia = $(".gerarchia.no-padding");
     $gerarchia.css({
     "border-color": "#dcdcdc",
     "border-right-width": "1px",
     "border-top-width": "1px",
     "border-bottom-width": "0px",
     "border-left-width": "0px",
     "border-style": "solid"
     });
     var $active = $(".active.text-tab");
     $active.css({
     "border-color": "#dcdcdc",
     "border-right-width": "0px",
     "border-top-width": "1px",
     "border-bottom-width": "0px",
     "border-left-width": "0px",
     "border-style": "solid"
     });*/
    /**
     * MFADEV-190
     */
    $('.js-ecommerce').parents('.form-group').addClass('form-group-ecommerce');
});
