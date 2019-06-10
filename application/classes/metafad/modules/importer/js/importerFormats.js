/**
 * Per POLODEBUG-380,237
 */
function MetaImportFormats(){
    var formats = {
        "trc": "(ICCD) TRC 92",
        "iccdxml": "(ICCD) XML",
        "sbn": "SBN",
        "eadeac": "EAD/EAC",
        "tei": "TEI-MS"
    };

    var conversion = {
        "gestione-dati/authority/archivi": ["eadeac"],
        "tei-manoscritto": ["tei"],
        "metafad.sbn.modules.sbnunimarc": ["sbn"],
        "gestione-dati/authority/sbn": ["sbn"],
        "aut200_alias": ["trc", "iccdxml"],
        "aut300_alias": ["trc", "iccdxml"],
        "aut400_alias": ["trc", "iccdxml"],
        "bib200_alias": ["trc", "iccdxml"],
        "bib300_alias": ["trc", "iccdxml"],
        "bib400_alias": ["trc", "iccdxml"],
        "schedad200_alias": ["trc", "iccdxml"],
        "schedad300_alias": ["trc", "iccdxml"],
        "schedaf200_alias": ["trc", "iccdxml"],
        "schedaf300_alias": ["trc", "iccdxml"],
        "schedaf400_alias": ["trc", "iccdxml"],
        "schedaoa200_alias": ["trc", "iccdxml"],
        "schedaoa300_alias": ["trc", "iccdxml"],
        "schedami200_alias": ["trc", "iccdxml"],
        "schedami300_alias": ["trc", "iccdxml"],
        "schedas200_alias": ["trc", "iccdxml"],
        "schedas300_alias": ["trc", "iccdxml"],
        "aut200": ["trc", "iccdxml"],
        "aut300": ["trc", "iccdxml"],
        "aut400": ["trc", "iccdxml"],
        "bib200": ["trc", "iccdxml"],
        "bib300": ["trc", "iccdxml"],
        "bib400": ["trc", "iccdxml"],
        "schedad200": ["trc", "iccdxml"],
        "schedad300": ["trc", "iccdxml"],
        "schedaf200": ["trc", "iccdxml"],
        "schedaf300": ["trc", "iccdxml"],
        "schedaf400": ["trc", "iccdxml"],
        "schedaoa200": ["trc", "iccdxml"],
        "schedaoa300": ["trc", "iccdxml"],
        "schedami200": ["trc", "iccdxml"],
        "schedami300": ["trc", "iccdxml"],
        "schedas200": ["trc", "iccdxml"],
        "schedas300": ["trc", "iccdxml"],
        "aut300artin": ["trc", "iccdxml"],
        "bib300artin": ["trc", "iccdxml"],
        "schedad300artin": ["trc", "iccdxml"],
        "schedami300artin": ["trc", "iccdxml"],
        "schedas300artin": ["trc", "iccdxml"]
    };

    /**
     * Restituisce quali formati inserire
     * @param scheda
     * @returns {Array}
     */
    this.getFormats = function(scheda){
        var arr = conversion[scheda] ? conversion[scheda] : [];
        return arr.map(function(format){return {val: formats[format], key: format};});
    }
}