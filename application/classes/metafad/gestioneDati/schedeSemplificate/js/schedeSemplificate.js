$(document).on('change','#form',function(){
    var value = $('#form').select2('val');

    $.ajax({
      url: Glizy.ajaxUrl + '&controllerName=metafad.gestioneDati.schedeSemplificate.controllers.ajax.RefreshFieldsList',
      type: 'POST',
      data: {
        moduleName: value
      },
      success: function (data, textStatus, jqXHR) {
        $('#fieldList').replaceWith(data);
        $('input').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
            increaseArea: '20%' // optional
        });
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      }
    });
});

$(document).ready(function(){
  $(document).on('ifClicked', '.checkbox-section',function(event){
    var check = $(this).prop('checked');
    if(check == false) {
      var action = 'check';
    }
    else {
      var action = 'uncheck';
    }
    $(this).parent().parent().siblings('.children').find('input').each(function(){
      $(this).iCheck(action);
    });
  });

  $(document).on('ifChecked','.checkbox-subsection',function(event){
    $(this).parents('.children').siblings('h2').find('input').iCheck('check');
    if($(this).parents('.row').data('parentid') !== undefined)
    {
      $('div[data-parent="'+$(this).parents('.row').data('parentid')+'"]').attr('data-toggled',true);
      $('div[data-parent="'+$(this).parents('.row').data('parentid')+'"]').find('input').iCheck('check');
    }

    if($(this).parents('.row').data('parent') !== undefined && $(this).parents('.row').attr('data-toggled') != 'true')
    {
      $('div[data-parentid="'+$(this).parents('.row').data('parent')+'"]').find('input').iCheck('check');
    }
  });

  $(document).on('ifUnchecked','.checkbox-subsection',function(event){
    $(this).parents('.children').siblings('h2').find('input').iCheck('uncheck');
    if($(this).parents('.row').data('parentid') !== undefined)
    {
      $('div[data-parent="'+$(this).parents('.row').data('parentid')+'"]').attr('data-toggled',false);
      //$('div[data-parent="'+$(this).parents('.row').data('parentid')+'"]').find('input').iCheck('uncheck');
    }

    if($(this).parents('.row').data('parent') !== undefined && $(this).parents('.row').attr('data-toggled') != 'false' && $(this).attr('data-type') !== 'mandatory')
    {
      $('div[data-parentid="'+$(this).parents('.row').data('parent')+'"]').find('input').iCheck('uncheck');
    }
  });


  $('.js-save').on('click',function(){
    //Intercetto la chiamata di salvataggio per creare un json a partire dai campi
    //selezionati
    if($('#name').val() != '')
    {
      $('#name').attr('readonly',true);
    }
    var jsonFields = '{"fields": [';
    $(document).find('input:checked').each(function(){
      jsonFields += '{"name":"'+$(this).attr('name')+'","type":"'+$(this).attr('data-type')+'","field":"'+$(this).attr('data-field')+'"},';
    });
    jsonFields = jsonFields.substring(0, jsonFields.length - 1);
    jsonFields += ']}';
    $('#fieldJson').val(jsonFields);

    var type = $(this).data('type');
    if(type == 'save')
    {
      $('.js-glizycms-saveNotClose').trigger('click');
    }
    else
    {
      $('.js-glizycms-saveClose').trigger('click');
    }
  });

  $( document ).ajaxComplete(function( event,request, settings ) {
    if(request.responseJSON !== undefined)
    {
      if(request.responseJSON.errors)
      {
        $('#name').attr('readonly',false);
      }
    }
  });
});
