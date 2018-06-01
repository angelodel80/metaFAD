$( document ).ready( function(){
    function cleanJoin(arr, s) {
      return arr, arr.filter(function(n){ return n != '' }).join(s);
    }

    $('#cognome,#nome,#qualificazione,#dateAttivita,#dataNascita,#dataNascita,#dataMorte').on('keyup change',function () {
        createIntestazione();
    });

    function createIntestazione() {
        var cogNom = cleanJoin([$('#cognome').val(), $('#nome').val()], ',');
        var ang = cleanJoin([$('#qualificazione').val(), $('#dateAttivita').val(),
                        cleanJoin([$('#dataNascita').val(), $('#dataMorte').val()], '-')], ' ; ');
        ang = ang ? '<' + ang + '>' : '';
        var intestazione = cleanJoin([cogNom, ang], ' ');
        $('#intestazione').val(intestazione);
    }
});