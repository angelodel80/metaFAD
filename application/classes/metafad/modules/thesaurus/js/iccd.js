function editDataGrid(data)
{
  var id = data.data('id');
  var val = data.val();
  var type = data.data('type');
  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.SaveInput',
    type: 'POST',
    data: {
      id: id,
      val: val,
      type: type
    },
    success: function (data, textStatus, jqXHR) {
      console.log('Dati salvati correttamente');
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore');
    }
  });
}

function saveParent(data)
{
  var idDiv = data[0].previousSibling.id;
  var value = $('#'+idDiv).select2('val');
  var key = data.data('key');
  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.SaveInput',
    type: 'POST',
    data: {
      id: key,
      val: value,
      type: 'parent'
    },
    success: function (data, textStatus, jqXHR) {
      console.log('Dati salvati correttamente');
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore');
    }
  });
};

$( document ).ajaxComplete(function( event,request, settings ) {
  if(request.responseJSON !== undefined)
  {
    if(request.responseJSON.type == 'searchAjax' || request.responseJSON.result == 'level')
    {
      var fieldName = 'thesaurusName';
      var thesaurus = $('#__id').val();
      var model = '';
      var query = '';
      var proxy = 'metafad.modules.thesaurus.models.proxy.ThesaurusDetailsProxy';

      var getId = '';

      $(".thesaurusParent").each(function(){
        //Seleziono il livello della voce, da aggiungere come parametro per la ricerca
        var proxyParams = '{"id":"'+thesaurus+'",';
        var level = $('.level.selected').find('[data-id='+$(this).data('key')+']').val();
        proxyParams = proxyParams + '"level":'+level+'}';
        $(this).select2({
          ajax: {
            url: Glizy.ajaxUrl + "&controllerName=org.glizycms.contents.controllers.autocomplete.ajax.FindTerm",
            dataType: 'json',
            delay: 250,
            data: function(term, page) {
              return {
                fieldName: fieldName,
                model: model,
                query: query,
                term: term,
                proxy: proxy,
                proxyParams: proxyParams,
                getId: getId
              };
            },
            results: function(data, page ) {
              return { results: data.result }
            }
          },
          escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
        });
        $('.select2-search-choice-close').css('display','inline');
        value = $(this).attr('data-val');
        key = $(this).attr('data-key');
        $(this).siblings('.thesaurusParent').css('width', '100%');
        $(this).siblings('.thesaurusParent').children().children('span').text(value);
      });
    }
  }

  $('.js-delete-row').on('click', function(e){
    e.stopPropagation();
    e.preventDefault();
    if (!confirm('Siete sicuri di voler cancellare il record selezionato?'))
    {
      return false;
    }
    else
    {
      var id = $(this).data('id');
      var that = $(this);
      $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.DeleteRecord',
        type: 'POST',
        data: {
          id: id
        },
        success: function (data, textStatus, jqXHR) {
          console.log('Dati eliminati correttamente');
          //TODO Sarebbe decisamente più corretto un refresh della datatable
          $('#dataGrid2').DataTable()._fnReDraw();
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log('Errore');
        }
      });
    }
  });

  $('.js-add-row').unbind().click( function(e){
    e.stopPropagation();
    e.preventDefault();

    var id = $('#__id').val();
    var value = $('#js-new-value').val();
    var key = $('#js-new-key').val();
    var level = $('#js-new-level').children('.level.selected').children('input').val();
    var parent = $('#s2id_thesaurusParent-0').select2('val');

    if(value === '' || key === '')
    {
      if(key === ''){
        $('#js-new-key').css('border','1px solid #FF0000');
        setTimeout(function(){ $('#js-new-key').css('border','1px solid #ccc'); }, 1500);
      }
      if(value === ''){
        $('#js-new-value').css('border','1px solid #FF0000');
        setTimeout(function(){ $('#js-new-value').css('border','1px solid #ccc'); }, 1500);
      }
      alert('Errore, inserire i dati mancanti obbligatori.');
    }
    else
    {
      var button = $(this);
      button.css('pointer-events','none');
      $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.AddRecord',
        type: 'POST',
        data: {
          id: id,
          value: value,
          key: key,
          level: level,
          parent: parent
        },
        success: function (data, textStatus, jqXHR) {
          console.log('Dati aggiunti correttamente');
          $('#dataGrid2').DataTable()._fnReDraw();
          button.css('pointer-events','auto');
          $('#js-new-value').val('');
          $('#js-new-key').val('');
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log('Errore');
        }
      });
    }
  });

});



window.onload = function(){
  //$('input[type=checkbox]').on('change', funImp);

  //$('#importFile').attr('accept','.xls,.xlsx,.csv');

  $('#relatedBoardIccd .GFEEmptyMessage').text('Premere il tasto "Aggiungi scheda" per inserire una nuova scheda');
  plusIcon = '<i class="fa fa-plus"></i>';
  $('.GFEButtonContainer').prepend(plusIcon);
  $('.import.link').removeAttr("href");
  $('.import.link').attr("onclick", "$('#importDataAlert').modal();");
  $('.export.link').removeAttr("href");
  $('.export.link').attr("onclick", "exportXSL()");
  $('.button-import a').attr("onclick", "event.stopPropagation(); event.preventDefault();$('#importDataAlert').modal();");

  $('.renderLevel').each( function(){
    level = $(this).children('.formItemHidden').children().attr('value');
    if(level && level == 0){
      level = 'tutti';
    }
    $(this).children().each( function(){
      if($(this).children('input[type=button]').attr('value')==level){
        $(this).attr('class', 'level selected');
      }
    });
  });

  $('input[type=file]').on('change', prepareUpload);

  function prepareUpload(event) {
    files = event.target.files;
  }

  $('.js-glizycms-file').on('click', uploadFiles);
  $('.js-glizycms-importMassive').on('click', uploadFilesMassive);

  function uploadFiles(event) {

    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening

    // START A LOADING SPINNER HERE
    var importType = $('#importType').val();
    var idClicked = event.delegateTarget.id;

    $('#'+idClicked).after('<img class="spinner" src="application/template/img/spinner.gif"/>');

    // Create a formdata object and add the files
    var data = new FormData();
    $.each(files, function (key, value) {
      data.append(key, value);
      var cancella = $('#CancellaTutti').is(':checked');
      var sostituisci = $('#SostituisciRecord').is(':checked');
      data.append('Cancella tutti', cancella );
      data.append('Sostituisci record', sostituisci);
      data.append('Dizionario', $('#__id').val());
    });

    if(importType == 'import1')
    {
      var urlParameter = 'import';
    }
    else if(importType == 'import2')
    {
      var urlParameter = '&controllerName=metafad.modules.thesaurus.controllers.ajax.ImportDictionaryGE';
    }
    else if(importType == 'import3')
    {
      var urlParameter = '&controllerName=metafad.modules.thesaurus.controllers.ajax.ImportDictionaryAL';
    }

    $.ajax({
      url: Glizy.ajaxUrl + urlParameter,
      type: 'POST',
      data: data,
      cache: false,
      dataType: 'json',
      processData: false, // Don't process the files
      contentType: false, // Set content type to false as jQuery will tell the server its a query string request
      success: function (data, textStatus, jqXHR) {
        if (typeof data.error === 'undefined') {
          // Success so call function to process the form
          $('#dataGrid2').DataTable()._fnReDraw();
          $('#importDataAlert').modal('hide');
          alert('Dati importati correttamente');
          $('.spinner').remove();
          location.reload();
        }
        else {
          // Handle errors here
          console.log('ERRORS: ' + data.error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        // Handle errors here
        alert(jqXHR.statusText);
        console.log('ERRORS: ' + jqXHR.statusText);
        // STOP LOADING SPINNER
      }
    });
  }

  function uploadFilesMassive(event) {

    event.stopPropagation(); // Stop stuff happening
    event.preventDefault(); // Totally stop stuff happening

    // START A LOADING SPINNER HERE
    var importType = $('#importType').val();
    var idClicked = event.delegateTarget.id;

    $('#'+idClicked).after('<img class="spinner" src="application/template/img/spinner.gif"/>');

    // Create a formdata object and add the files
    var data = new FormData();
    $.each(files, function (key, value) {
      data.append(key, value);
      var cancella = $('#CancellaTutti').is(':checked');
      var sostituisci = $('#SostituisciRecord').is(':checked');
      data.append('Cancella tutti', cancella );
      data.append('Sostituisci record', sostituisci);
      data.append('Dizionario', $('#__id').val());
    });

    if(importType == 'import1')
    {
      var urlParameter = 'ImportMassive';
    }
    else if(importType == 'import2')
    {
      var urlParameter = '&controllerName=metafad.modules.thesaurus.controllers.ajax.ImportMassiveGE';
    }
    else if(importType == 'import3')
    {
      var urlParameter = '&controllerName=metafad.modules.thesaurus.controllers.ajax.ImportMassiveAL';
    }
    else if(importType == 'import4')
    {
      var urlParameter = '&controllerName=metafad.modules.thesaurus.controllers.ajax.ImportMassiveAUTO';
    }

    $.ajax({
      url: Glizy.ajaxUrl + urlParameter,
      type: 'POST',
      data: data,
      cache: false,
      dataType: 'json',
      processData: false, // Don't process the files
      contentType: false, // Set content type to false as jQuery will tell the server its a query string request
      success: function (data, textStatus, jqXHR) {
        if (typeof data.error === 'undefined') {
          var a = data.result;
          var index,len;
          var dizionari = '';
          for (index = 0, len = a.length; index < len; ++index) {
            dizionari += a[index] + "\n";
          }
          // Success so call function to process the form
          $('#dataGridForms').DataTable()._fnReDraw();
          $('#importDataAlert').modal('hide');
          alert('Dizionari in corso di importazione\n\nDizionari che verranno importati:\n'+dizionari);
          $('.spinner').remove();
        }
        else {
          // Handle errors here
          console.log('ERRORS: ' + data.error);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        // Handle errors here
        alert(jqXHR.statusText);
        console.log('ERRORS: ' + jqXHR.statusText);
        // STOP LOADING SPINNER
      }
    });
  }

  //Reinizializzazione delle select2 con la lista del campi del model
  //TODO sistemare problema di chiusura automatica al primo click
  $('.thesaurusModel').find('a').on('click',function(e){
    var idModuleDiv = $(this).parent().attr('id');

    var arraySelect = new Array();
    $('input[data-t]').each(function(){
      arraySelect.push($(this).siblings('div').attr('id'));
    });
    var index = arraySelect.indexOf(idModuleDiv);

    var data = $('#'+arraySelect[index - 1]).select2('val');

    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.GetModule',
      type: 'POST',
      data: {id:data},
      dataType: 'json',
      success: function (data, textStatus, jqXHR) {
        var model = data.result;
        var instance = $('#'+idModuleDiv).siblings('input').data('instance');
        instance.$element.data('proxy_params','{##modelName##:##' + model + '##}');
        instance.initialize(instance.$element);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Error');
      }
    });
  });
};

//Reinizializzo i componenti select2 in base al modulo selezionato
$(document).on('change','.userInstitute',function(){
    var idSelect2 = $(this).find('.select2-container').attr('id');

    var arraySelect = new Array();
    $('input[data-t]').each(function(){
      arraySelect.push($(this).siblings('div').attr('id'))
    });

    var index = arraySelect.indexOf(idSelect2);

    var model = $('#'+idSelect2).select2('val');
    var instance = $('#'+arraySelect[index + 1]).siblings('input').data('instance');
    instance.$element.data('proxy_params','{##modelName##:##' + model + '##}');
    instance.initialize(instance.$element);
});

function exportXSL(){
  var id = $('#__id').val();
  $.ajax({
    url: Glizy.ajaxUrl + 'export',
    type: 'POST',
    data: {
      id: id
    },
    success: function (data, textStatus, jqXHR) {
      var win = window.open(data.result,'_blank');
      win.focus();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('error');
    }
  });
}

function clickLevel(element){
  element.attr("id", "iccd_thesaurus_level");
  element.parent().siblings().each(function(){
    if($(this).attr("class") != "formItemHidden"){
      $(this).attr("class", "level");
    }
  });
  element.parent().siblings().children().removeAttr("id");
  element.parent().attr("class", "level selected");
  var val = element.attr('value');
  element.parent().siblings('.formItemHidden').children().attr('value', val);
};

function clickLevelAndSave(element){
  element.attr("id", "iccd_thesaurus_level");
  element.parent().siblings().each(function(){
    if($(this).attr("class") != "formItemHidden"){
      $(this).attr("class", "level");
    }
  });
  element.parent().siblings().children().removeAttr("id");
  element.parent().attr("class", "level selected");
  var val = element.attr('value');
  element.parent().siblings('.formItemHidden').children().attr('value', val);

  var id = element.data('id');
  var val = element.val();
  var type = 'level';
  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.modules.thesaurus.controllers.ajax.SaveInput',
    type: 'POST',
    data: {
      id: id,
      val: val,
      type: type
    },
    success: function (data, textStatus, jqXHR) {
      console.log('Dati salvati correttamente');
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore');
    }
  });
};

function clickLevelNew(element){
  element.attr("id", "iccd_thesaurus_level");
  element.parent().siblings().each(function(){
    if($(this).attr("class") != "formItemHidden"){
      $(this).attr("class", "level");
    }
  });
  element.parent().siblings().children().removeAttr("id");
  element.parent().attr("class", "level selected");
  var val = element.attr('value');
  element.parent().siblings('.formItemHidden').children().attr('value', val);

  var fieldName = 'thesaurusName';
  var thesaurus = $('#__id').val();
  var model = '';
  var query = '';
  var proxy = 'metafad.modules.thesaurus.models.proxy.ThesaurusDetailsProxy';

  var getId = '';

  thesaurusParent = $('#thesaurusParent-0');

    //Seleziono il livello della voce, da aggiungere come parametro per la ricerca
    var proxyParams = '{"id":"'+thesaurus+'",';
    var level = $('.level.selected').find('[data-id='+$(thesaurusParent).data('key')+']').val();
    proxyParams = proxyParams + '"level":'+level+'}';
    $(thesaurusParent).select2({
      ajax: {
        url: Glizy.ajaxUrl + "&controllerName=org.glizycms.contents.controllers.autocomplete.ajax.FindTerm",
        dataType: 'json',
        delay: 250,
        data: function(term, page) {
          return {
            fieldName: fieldName,
            model: model,
            query: query,
            term: term,
            proxy: proxy,
            proxyParams: proxyParams,
            getId: getId
          };
        },
        results: function(data, page ) {
          return { results: data.result }
        }
      },
      escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
    });
    $('.select2-search-choice-close').css('display','inline');
    value = $(thesaurusParent).attr('data-val');
    key = $(thesaurusParent).attr('data-key');
    $(thesaurusParent).siblings('.thesaurusParent').css('width', '100%');
    $(thesaurusParent).siblings('.thesaurusParent').children().children('span').text(value);
};
