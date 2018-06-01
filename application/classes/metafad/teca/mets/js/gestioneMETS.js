$(document).ready(function(){
  $('.link.metadata').removeAttr("href");

  $('body').on('click','.link.metadata',function(e){
    e.stopPropagation();
    e.preventDefault();
    var row = $(this).closest('.GFERowContainer');
    row.find('#media-dam-mediapicker').click();
  });

  $('.struTree').on('click','.js-caret',function(e){
    $(this).toggleClass('fa-caret-right');
    $(this).toggleClass('fa-caret-down');
    $(this).parent().siblings('ul').first().toggle(200);
  });

  $('.struTree').on('click','.js-showElements',function(e){
    e.stopPropagation();
    e.preventDefault();
    //Settaggio dell'elemento attivo
    $('.struTree').find('div').removeClass('elementActive');
    $(this).children('div').addClass('elementActive');
    //Estraggo la chiave per selezione elementi da mostrare
    var key = $(this).attr('data-key');
    //Nascondo elementContent (serve solo la prima volta in realtà)
    $('#elementContent').addClass('hide');
    //Resetto tab
    $('.nav-tabs-elements').removeClass('active');
    //Settaggio key elemento da salvare
    $('.actionSaveElement').attr('data-key',key);
    //Mostro sezioni azioni e file
    $('.actionsStru').removeClass('hide');
    $('#fileTabsStru').removeClass('hide');
    //Setto la chiave dei tab con quella dell'elemento selezionato
    $('.nav-tabs-elements').each(function(){
      $(this).attr('data-key',key);
    });
    //Resetto i tab con attivo quello per le immagini
    $('#tab-image').addClass('active');
    //mostro i media necessari in base a key e type
    $('.element-media').each(function(){
      $(this).addClass('hide');
      if($(this).attr('data-element') == key && $(this).attr('data-type') == 'IMAGE')
      {
        $(this).removeClass('hide');
      }
    });
  });

  $('#file').on('click','.deleteMediaButton',function(){
    var idMedia = $(this).attr('data-id');
    var that = $(this);
    if(confirm('Attenzione: questa operazione cancellerà tutti i metadati relativi al media e lo scollegherà dal MAG. Proseguire?'))
    {
      $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.DeleteSingleMedia',
        type: 'POST',
        data: {
          id: idMedia
        },
        success: function (data, textStatus, jqXHR) {
          that.parents('.singleMediaDiv').toggle(200);
          that.parents('.singleMediaDiv').addClass('hide');
          if(that.parents('.mediaContainer').find('.singleMediaDiv').length == 1)
          {
            that.parents('.mediaContainer').append('<div class="groupMessage">Non esiste nessun media di questo genere.</div>');
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log('Errore');
        }
      });
    }
  });

  $('#showElements').on('click','.element-media',function(){
    $(this).toggleClass('selected-media');
  });

  $('.btn-dam').on('click',function(){
    var id = $('#__id').val();
    if(id == '')
    {
      alert('Attenzione! Prima di effettuare questo genere di operazioni è necessario salvare (almeno in bozza) il MAG');
      return;
    }
    var media = $(this).siblings('.form-group').find('input').val();
    $('.btn-dam').after('<span class="spinner-operation"><img src="application/template/img/spinner.gif"/> Operazione in corso. Attendere...</span>');
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.CreateMediaFromDAM',
      type: 'POST',
      data: {
        media: media,
        id: id
      },
      success: function (data, textStatus, jqXHR) {
        $('#fileTabs_content').remove();
        $('#fileTabs').html(data);
        alert('Importazione completata.');
        $('.spinner-operation').remove();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        window.alert('Attenzione, si è verificato un errore.');
        $('.spinner-operation').remove();
      },
      complete: function(data){
        refreshLinkedImages();
      }
    });
  });

  $('#showElements').on('click','.js-actionStru',function(){
    var type = $(this).attr('data-type');
    var stru = $(this).attr('data-stru');
    var id = $('#__id').val();
    var linkedStru = $('#linkedStru').val();
    if(linkedStru == '')
    {
      window.alert('Attenzione, si è verificato un errore. Collegare un metadato strutturale prima di eseguire questa operazione.');
      return;
    }

    //Ottengo valore opzioni di Importazione
    //0 - STRU FISICA
    //1 - STRU LOGICA
    //2 - ENTRAMBE LE SOPRA CITATE
    var option = $('#stru_options').val();

    if(id == '')
    {
      alert('Attenzione! Prima di effettuare questo genere di operazioni è necessario salvare (almeno in bozza) il MAG');
      return;
    }
    //Gestione salvataggio immagini collegate a element STRUMAG
    if(!confirm('Questa operazione modificherà direttamente i dati nel database, non sarà necessario un ulteriore salvataggio. Eventuali media già inseriti non saranno reimportati. Proseguire? (l\'operazione può richiedere tempo se la mole di dati è consistente)'))
    {
      return;
    }
    else
    {
      $('#showElements').find('h2').after('<span class="spinner-operation"><img src="application/template/img/spinner.gif"/> Operazione in corso. Attendere...</span>');
    }
    if(type == 'saveAll')
    {
      var importAll = false;
      if(option == 0 || option == 2)
      {
        var importAll = true;
        $.ajax({
          url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.CreateMedia',
          type: 'POST',
          data: {
            type: type,
            stru: stru,
            key: null,
            id: id,
            physical: true
          },
          success: function (data, textStatus, jqXHR) {
            $('#file').html(data);
            alert('Importazione completata.');
            $('.spinner-operation').remove();
          },
          error: function (jqXHR, textStatus, errorThrown) {
            window.alert('Attenzione, si è verificato un errore. Collegare un metadato strutturale prima di eseguire questa operazione.');
            $('.spinner-operation').remove();
          },
          complete: function(data){
            refreshLinkedImages();
          }
        });
      }
      if(option == 1 || option == 2)
      {
        $.ajax({
          url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.CreateLogicalStru',
          type: 'POST',
          data: {
            type: type,
            stru: stru,
            key: null,
            id: id
          },
          success: function (data, textStatus, jqXHR) {
            $('#logicalStru').val(data.result.logicalStru);
            if(importAll != true){
              alert('Importazione completata.');
            }
            $('.spinner-operation').remove();
          },
          error: function (jqXHR, textStatus, errorThrown) {
            window.alert('Attenzione, si è verificato un errore. Collegare un metadato strutturale priuma di eseguire questa operazione.');
            $('.spinner-operation').remove();
          },
          complete: function(data){
            refreshLinkedImages();
          }
        });
      }
      if(option == 1)
      {
        //Se sto importando l'intera stru solo logica
        //rientro nel caso del MAG PADRE NUDO CON FIGLI VESTITI
        $('#flagVestito').val(false);
        $('#flagParent').val(true);
      }
      if(option == 2)
      {
        //Se sto importando l'intera stru sia logica che fisica
        //rientro nel caso del MAG PADRE VESTITO CON FIGLI NUDI
        $('#flagVestito').val(true);
        $('#flagParent').val(true);
      }
    }
    else if(type == 'saveChecked')
    {
      var importChecked = false;
      if(option == 0 || option == 2)
      {
        var key = new Array();
        $('.struTree').find('input').each(function(){
          if($(this).is(':checked'))
          {
            key.push($(this).attr('data-key'));
          }
        });
        if(key.length > 0)
        {
          $.ajax({
            url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.CreateMedia',
            type: 'POST',
            data: {
              type: type,
              stru: stru,
              key: key,
              id: id,
              option: option
            },
            success: function (data, textStatus, jqXHR) {
              $('#fileTabs_content').remove();
              $('#fileTabs').html(data);
              alert('Importazione completata.');
              var importChecked = true;
              $('.spinner-operation').remove();
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.log('Errore');
            },
            complete: function(data){
              refreshLinkedImages();
            }
          });
        }
        else
        {
          alert('Nessun nodo selezionato. Selezionare almeno un nodo.');
          $('.spinner-operation').remove();
          var erroreNodi = true;
        }
      }
      if(option == 1 || option == 2)
      {
        var key = new Array();
        $('.struTree').find('input').each(function(){
          if($(this).is(':checked'))
          {
            key.push($(this).attr('data-key'));
          }
        });
        if(key.length > 0)
        {
          $.ajax({
            url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.CreateLogicalStru',
            type: 'POST',
            data: {
              type: type,
              stru: stru,
              key: key,
              id: id,
              option: option
            },
            success: function (data, textStatus, jqXHR) {
              console.log(data);
              $('#logicalStru').val(data.result.logicalStru);
              if(importChecked != true){
                alert('Importazione completata.');
              }
              $('.spinner-operation').remove();
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.log('Errore');
            },
            complete: function(data){
              refreshLinkedImages();
            }
          });
        }
        else if(erroreNodi != true)
        {
          alert('Nessun nodo selezionato. Selezionare almeno un nodo.');
          $('.spinner-operation').remove();
        }
      }
      $('#flagParent').val(false);
      $('#flagVestito').val(false);
      if(option == 2)
      {
        $('#flagVestito').val(true);
      }
    }
    else if(type == 'deleteAll')
    {
      if(option == 0 || option == 2)
      {
        $.ajax({
          url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.DeleteMedia',
          type: 'POST',
          data: {
            id: id,
            option: option
          },
          success: function (data, textStatus, jqXHR) {
            $('#fileTabs_content').remove();
            $('#fileTabs').html(data);
            alert('Cancellazione completata.');
            $('.spinner-operation').remove();
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.log('Errore');
          },
          complete: function(data){
            refreshLinkedImages();
          }
        });
      }
      if(option == 1 || option == 2)
      {
        alert('STRU logica scollegata dal MAG');
        $('#logicalStru').val('');
        $('.spinner-operation').remove();
      }

      if(option == 0)
      {
        $('#flagVestito').val(false);
      }
      if(option == 1 || option == 2)
      {
        $('#flagVestito').val(false);
        $('#flagParent').val(false);
      }

    }
    else if(type == 'saveCheckedMedia')
    {

      var key = new Array();
      $('#showElements').find('.selected-media').each(function(){
        key.push($(this).attr('data-mediaid'));
      });
      if(key.length > 0)
      {
        $.ajax({
          url: Glizy.ajaxUrl + '&controllerName=metafad.teca.mets.controllers.ajax.CreateMediaFromList',
          type: 'POST',
          data: {
            stru: stru,
            key: key,
            id: id
          },
          success: function (data, textStatus, jqXHR) {
            $('#fileTabs_content').remove();
            $('#fileTabs').html(data);
            alert('Importazione singoli media completata.');
            $('.spinner-operation').remove();
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.log('Errore');
          },
          complete: function(data){
            refreshLinkedImages();
          }
        });
      }
      else
      {
        alert('Nessun media selezionato. Selezionare almeno un media.');
        $('.spinner-operation').remove();
      }
    }

    if(option == 0){
      $('a[data-toggle="tab-next"]').click();
    }
  });

  $('#showElements').on('click','.nav-tabs-elements',function(){
    var type = $(this).attr('data-type');
    var key = $(this).attr('data-key');
    //Mostro i media di un certo tipo collegati ad un certo elemento
    $('.element-media').each(function(){
      $(this).addClass('hide');
      if($(this).attr('data-element') == key && $(this).attr('data-type') == type)
      {
        $(this).removeClass('hide');
      }
    });
  });

  $('#BIB_level').on('change',function(){
    changeVisibilityPiece($(this).val());
  });

  $('#file').on('click','#fileTabs a',function(){
    $('.tab-pane-media').addClass('hide');
    $($(this).data('target')).removeClass('hide');
  });

  $('.js-element').on('click',function(){
    var start = $(this).data('start');
    var stop = $(this).data('stop');
    var type = $(this).data('type');
    var idParent = $('#__id').val();
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.GetElement',
      type: 'POST',
      data: {
        start: start,
        stop: stop,
        type: type,
        idParent: idParent
      },
      success: function (data, textStatus, jqXHR) {
        $('#elementContent').html(data.result);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      }
    });
  });

  $('input[name="linkedStru"]').on('change',function(){
    var value = $(this).siblings('div').select2('data');
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.StruFormat',
      type: 'POST',
      data: {
        value: value
      },
      success: function (data, textStatus, jqXHR) {
        console.log('OK');
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      },
      complete: function(data){
        $('.struTree').first().html(data.responseJSON.result[0]);
        $('#showElements').html(data.responseJSON.result[1]);
        $('.js-caret').parent().siblings('ul').each(function(){
          $(this).toggle();
        });
        $('input').iCheck({
          checkboxClass: 'icheckbox_square-blue',
          radioClass: 'iradio_square-blue',
          increaseArea: '20%' // optional
        });
      }
    });
  });

  $('.struTree').on('click','.js-saveStru',function(e){
    e.stopPropagation();
    e.preventDefault();
  });
});

$(document).on('change','#linkedFormType',function(){
  var model = $('#linkedFormType').val();
  var instance = $('#linkedForm').data('instance');
  instance.$element.data('proxy_params','{##modelName##:##' + model + '##}');
  instance.initialize(instance.$element);
});

$(document).on('change','#linkedForm',function(){
  var bid = $('#linkedForm').select2('val');
  var type = $('#linkedFormType').val();
  //Il servizio GetBibFromSBN va utilizzato solo per unimarc
  if(type == 'metafad.sbn.modules.sbnunimarc' && bid != '')
  {
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.GetBibFromSBN',
      type: 'POST',
      data: {
        bid: bid
      },
      success: function (data, textStatus, jqXHR) {
        countElements = populateFields(data.result);
        if(countElements > 0)
        {
          alert('Importazione da '+bid+' completata.');
        }
        else
        {
          alert('Attenzione: BID '+bid+' non disponibile.');
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      }
    });
  }
  else if((type == 'SchedaF400' || type == 'SchedaS300' || type == 'SchedaOA300' || type == 'SchedaD300') && bid != '')
  {
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.GetMagFromRecord',
      type: 'POST',
      data: {
        id: bid
      },
      success: function (data, textStatus, jqXHR) {
        countElements = populateFields(data.result);
        if(countElements > 0)
        {
          alert('Importazione da Scheda ICCD (ID sistema:'+bid+') completata.');
        }
        else
        {
          alert('Attenzione: la scheda scelta non è disponibile.');
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      }
    });
  }
  else if((type == 'archivi.models.UnitaArchivistica' || type == 'archivi.models.UnitaDocumentaria') && bid != '')
  {
    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.GetMagFromRecord',
      type: 'POST',
      data: {
        id: bid
      },
      success: function (data, textStatus, jqXHR) {
        countElements = populateFields(data.result);
        if(countElements > 0)
        {
          alert('Importazione da Archivi (ID sistema:'+bid+') completata.');
        }
        else
        {
          alert('Attenzione: la scheda scelta non è disponibile.');
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      }
    });
  }
});

function populateFields(result)
{
  var countElements = 0;
  $('#dc-addRowBtn').click();
  var baseName = 'dc-dc0-';
  for(var d in result)
  {
    countElements++;
    var value = result[d];
    if(typeof(value) == 'string')
    {
      $('#'+d).val(value);
    }
    else
    {
      console.log(d);
      var count = 0;
      for(var e in value)
      {
        if (!$('#' + d).find('input[name="' +baseName+d+'_value"]').length)
        {
          $('#' +baseName+d+'-addRowBtn').click();
        }
        $('#'+d).find('input[name="'+d+'_value"]').val(value[e]);
        if(d == 'BIB_dc_relation')
        {
          $('#'+d).find('input[name="'+d+'_value"]').select2("data", { id: 0, text: value[e] });
        }
        $('#'+d).find('.select2-choice').find('span').html(value[e]);
        count++;
      }
    }
  }
  return countElements;
}

function refreshLinkedImages()
{
  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.teca.MAG.controllers.ajax.RefreshLinkedImages',
    type: 'POST',
    data: {
    },
    success: function (data, textStatus, jqXHR) {
      $('#panelImages').html(data);
      $("a.js-lightbox-image").colorbox({transition:"none", width:"95%", height:"95%", slideshow:false, slideshowAuto:false});
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore');
    }
  });
}