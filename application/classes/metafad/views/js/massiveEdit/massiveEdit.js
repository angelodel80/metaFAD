var ids = new Array();

$(document).ready(function(){
  var editMassive = $('.edit-massive').children('a');
  editMassive.data('href',editMassive.attr('href'));
  editMassive.removeAttr('href');

  $('#dataGridAddButton').append($('.edit-massive'));
  $('#dataGridAddButton').append($('.edit-massive-normalize'));

  $('#dataGridAddButton').css('display','-webkit-inline-box');

  var normalizeMassive = $('.edit-massive-normalize').children('a');
  normalizeMassive.data('href',normalizeMassive.attr('href'));
  normalizeMassive.removeAttr('href');

  //Aggiorno i checkbox se la tabella viene ridisegnata, controllando
  //se alcuni record sono già stati selezionati
  $('#dataGridFiltered').on('draw.dt',function(){
    $('#dataGridFiltered').find('input').each(function(){
      if(ids.indexOf($(this).attr('data-id')) > -1)
      {
        $(this).prop('checked',true);
      }
    });
  });

  $('#dataGrid').on('draw.dt', function () {
    $('#dataGrid').find('input').each(function () {
      if (ids.indexOf($(this).attr('data-id')) > -1) {
        $(this).prop('checked', true);
      }
    });
  });

  $('body').on('change','.selectionflag',function(){
    var checked = $(this).prop('checked');
    var id = $(this).attr('data-id');
    if (checked) {
      if (!ids.includes(id)) 
      {
        ids.push(id);
      }
    }
    else{
      var index = ids.indexOf(id);
      if (index != -1) 
      {
        ids.splice(index, 1);
      }
    }

    if ($('#__selectedIds').length > 0) 
    {
      $('#__selectedIds').val(ids.join());
    }
  });

  normalizeMassive.on('click',function(){
    var link = $(this).data('href');
    idList = '';
    ids.sort();
    for(var i = 0; i < ids.length; i++)
    {
      idList += ids[i] + '-';
    }
    if(idList != '' && confirm('Sei sicuro di voler eseguire questa operazione? (Non sarà possibile tornare allo stato precedente.)'))
    {
      window.location.href = link + idList.substring(0, idList.length - 1) + '/';
    }
    else
    {
      window.alert('Non è stato selezionato nessun record.');
    }
  });

  editMassive.on('click',function(){
    var link = $(this).data('href');
    idList = '';
    ids.sort();
    for(var i = 0; i < ids.length; i++)
    {
      idList += ids[i] + '-';
    }
    if(idList != '')
    {
      $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.gestioneDati.massiveEdit.controllers.ajax.StoreIds',
        type: 'get',
        action: 'modify',
        dataType: 'json',
        data: {
          ids: ids
        },
        success: function (data, textStatus, jqXHR) {
          if (data.status === true) {
            window.location.href = link + data.result + '/';
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          alert('Attenzione: sono state selezionate troppo schede e questo può avere effetti negativi sulle prestazioni del sistema. Si consiglia un numero non superiore alle 400 simultaneamente.');
        }
      })
    }
    else
    {
      window.alert('Non è stato selezionato nessun record.');
    }
  });

  //statesArray viene costruito in FormList.php
  $('.js-glizycms-save-novalidation').on('click',function(e){
    var action = $(this).data('action');
    var message = '';
    if(action == 'saveMassiveGroup')
    {
      return;
    }
    if(action == 'saveDraftMassive'){
      var state = 'bozza';
      for (var key in statesArray) {
        if(statesArray[key] != 'DRAFT' && statesArray[key] != 'PUBLISHED/DRAFT' )
        {
          message += key + ' non ha una versione di salvataggio allo stato desiderato\n';
        }
      }
      changeCheckValues();
    }
    else{
      var state = 'pubblicata';
      for (var key in statesArray) {
        if(statesArray[key] == 'DRAFT' && statesArray[key] != 'PUBLISHED/DRAFT')
        {
          message += key + ' non ha una versione di salvataggio allo stato desiderato\n';
        }
      }
      changeCheckValues();
    }
    if(message != '')
    {
      var endMessage = '\n\nLe schede sopra citate verranno modificate nell\'unico stato disponibile';
    }
    else {
      var endMessage = '\n\nTutte le schede hanno una versione di salvataggio in questo stato.';
    }
    window.alert('ATTENZIONE: stai effettuando in salvataggio allo stato "'+state+'"\n' + message + endMessage);
  });

  function changeCheckValues()
  {
    $('.js-empty-field').each(function(){
      if($(this).prop('checked') === true)
      {
        $(this).val('true');
      }
      else
      {
        $(this).val('false');
      }
    });
  }

});
