$(document).ready(function(){
  $('#uploadType').parents('.form-group').addClass('hide');

  function syncFormatsToType(val) {
    $('#format option').remove();
    var formats = new MetaImportFormats().getFormats(val);
    formats.forEach(function (a) {
      document.getElementById("format").add(new Option(a.val, a.key));
    });
  }

  var module = $('#module');
  module.on('change',function(){
    var val = $(this).val();

    syncFormatsToType(val);

    if(val == 'metafad.sbn.modules.sbnunimarc')
    {
      $('#div_sbnFolder').removeClass('hide');
      $('#div_sbnAutFolder').addClass('hide');
      $('#uploadType').parents('.form-group').removeClass('hide');
      $('#fileuploader').parents('.form-group').addClass('hide');
      $('#fileFromServer').parents('.form-group').addClass('hide');
      $('#overwriteScheda').parents('.form-group').addClass('hide');
      $('#overwriteAuthority').parents('.form-group').addClass('hide');
      $('#medias').addClass('hide');
    }
    else if(val == 'gestione-dati/authority/sbn')
    {
      $('#div_sbnAutFolder').removeClass('hide');
      $('#div_sbnFolder').addClass('hide');
      $('#uploadType').parents('.form-group').removeClass('hide');
      $('#fileuploader').parents('.form-group').addClass('hide');
      $('#fileFromServer').parents('.form-group').addClass('hide');
      $('#overwriteScheda').parents('.form-group').addClass('hide');
      $('#overwriteAuthority').parents('.form-group').addClass('hide');
      $('#medias').addClass('hide');
    }
    else
    {
      $('#div_sbnAutFolder').addClass('hide');
      $('#div_sbnFolder').addClass('hide');
      $('#uploadType').parents('.form-group').addClass('hide');
      $('#fileuploader').parents('.form-group').removeClass('hide');
      $('#fileFromServer').parents('.form-group').removeClass('hide');
      $('#overwriteScheda').parents('.form-group').removeClass('hide');
      $('#overwriteAuthority').parents('.form-group').removeClass('hide');
      $('#medias').removeClass('hide');
    }
  });

  syncFormatsToType(module.val());
});
