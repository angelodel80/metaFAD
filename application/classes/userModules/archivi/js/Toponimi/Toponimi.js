$( document ).ready( function(){
    function cleanJoin(arr, s) {
      return arr, arr.filter(function(n){ return n != '' }).join(s);
    }

    $('#nomeLuogo,#comuneAttuale,#denominazioneCoeva').on('keyup change',function () {
        createIntestazione();
    });

    function createIntestazione() {
        var toponimo = $('#nomeLuogo').val();
        var ang = cleanJoin([ toponimo, $('#comuneAttuale').val(), $('#denominazioneCoeva').val() ], ' ; ');
        ang = ang ? '<' + ang + '>' : '';
        var intestazione = cleanJoin([toponimo, ang], ' ');
        $('#intestazione').val(intestazione);
    }
});