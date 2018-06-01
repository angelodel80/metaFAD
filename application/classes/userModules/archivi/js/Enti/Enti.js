$( document ).ready( function(){
    function cleanJoin(arr, s) {
      return arr, arr.filter(function(n){ return n != '' }).join(s);
    }

    $('#denominazioneEnte,#dateEsistenza').on('keyup change',function () {
        createIntestazione();
    });

    function createIntestazione() {
        var denominazione = $('#denominazioneEnte').val();
        var ang = cleanJoin([$('#dateEsistenza').val()], ' ; ');
        ang = ang ? '<' + ang + '>' : '';
        var intestazione = cleanJoin([denominazione, ang], ' ');
        $('#intestazione').val(intestazione);
    }
});