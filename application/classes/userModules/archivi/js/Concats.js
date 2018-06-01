function MetaConcatUtils() {
    //POLODEBUG-310
    function generateTotalConsistence() {
        var consistenze = $('div[id^="undefined-consistenza"]');
        var objs = [];
        consistenze.each(function () {
            var tipo = $(this).find('div.form-group div.select2-container a.select2-choice > span').text();
            var qta = $(this).find('div.form-group input[name="quantita"]').val();

            objs.push({"t": (tipo ? tipo : "").trim(), "q": (qta ? qta : "").trim()});
        });
        return objs
            .map(function (el) {
                var q = el['q'];
                var t = el['t'] ? el['t'] : (q ? "N.D." : "");
                return [q, t].filter(function (a) {
                    return a;
                }).join(" ")
            })
            .filter(function(a){return a;})
            .join(", ");
    }

    //POLODEBUG-402: Tipologia (Supporto): Quantità Tipologia (Supporto);
    function generateTotalConsUnita(selectors){
        var extTipologia = $(selectors['extTipo']).text();
        var extSupporto = $(selectors['extSupporto']).text();

        var consistenze = $(selectors['consContainer']);
        var objs = [];
        consistenze.each(function () {
            var tipo = $(this).find(selectors['intTipo']).siblings('div.select2-container').find('a > span').text();
            var qta = $(this).find(selectors['intQuantita']).val();
            var sup = $(this).find(selectors['intSupporto']).siblings('div.select2-container').find('a > span').text();

            objs.push({"t": (tipo ? tipo : "").trim(), "q": (qta ? qta : "").trim(), "s": (sup ? sup : "").trim()});
        });

        var total = [];
        if (extTipologia) total.push(extTipologia);
        if (extSupporto) total.push("(" + extSupporto + ")");
        total = total.join(" ");

        var repeatable =
            objs
                .map(function (el) {
                    var q = el['q'];
                    var s = el['s'] ? "(" + el['s'] + ")" : "";
                    var t = el['t'] ? el['t'] : (q ? "N.D." : "");
                    return [q, t, s].filter(function (a) {
                        return a;
                    }).join(" ")
                })
                .filter(function(a){return a;})
                .join("; ");

        return [total, repeatable].filter(function(a){return a;}).join(": ");
    }

    this.generateConsistenza = function(type){
        var selectors;
        switch(type.toLowerCase()){
            case "ca":
                return generateTotalConsistence();
                break;
            case "ua":
                selectors = {
                    'extTipo': "#s2id_descrizioneFisica_tipologia > a > span",
                    'extSupporto': "#s2id_descrizioneFisica_supporto > a > span",
                    'consContainer': "div[id^='undefined-descrizioneFisica_consistenza']",
                    'intTipo': "input[name='consistenza_tipologia']",
                    'intSupporto': "input[name='consistenza_supporto']",
                    'intQuantita': "input[name='consistenza_quantita']"
                };
                return generateTotalConsUnita(selectors);
                break;
            case "ud":
                selectors = {
                    'extTipo': "#s2id_descrizioneFisica_tipologia > a > span",
                    'extSupporto': "#s2id_descrizioneFisica_supporto > a > span",
                    'consContainer': "div[id^='undefined-consistenza']",
                    'intTipo': "input[name='descrizioneFisicaSupporto_tipologia']",
                    'intSupporto': "input[name='supporto_supporto']",
                    'intQuantita': "input[name='quantita']"
                };
                return generateTotalConsUnita(selectors);
                break;
            default:
                return "";
        }
    }
}
