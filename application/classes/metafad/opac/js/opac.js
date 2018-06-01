$(document).ready(function(){
  $('.GFERowHandler').removeClass('GFERowHandlerExpanded');
  $('.GFERowHandler').addClass('addHandler');

  $('#fields-addRowBtn').on('click',function(){
    $('.GFERowHandler').removeClass('GFERowHandlerExpanded');
    $('.GFERowHandler').addClass('addHandler');
  });

  //Aggiorno i dati per il picker dei campi da collegare
  $('#section, #form, #archiveType').on('change',function(){
    var section = $('#section').val();
    var form = $('#form').val();
    var archive = $('#archiveType').val();
    $('input[name="linkedFields"]').each(function(){
      var instance = $(this).data('instance');
      instance.$element.data('proxy_params','{##section##:##' + section + '##,##form##:##' + form + '##,##archive##:##'+ archive +'##}');
      instance.initialize(instance.$element);
    });

    if(section == 'patrimonio')
    {
      $('#form').parents('.form-group').removeClass('hide');
      $('#archiveType').parents('.form-group').addClass('hide');
    }
    else if(section == 'archivi')
    {
      $('#form').parents('.form-group').addClass('hide');
      $('#form').val('');
      $('#archiveType').parents('.form-group').removeClass('hide');
    }
    else
    {
      $('#form').parents('.form-group').addClass('hide');
      $('#form').val('');
      $('#archiveType').parents('.form-group').addClass('hide');
    }
  });

  if($('#section').val() != 'patrimonio')
  {
    $('#form').parents('.form-group').addClass('hide');
  }

  if($('#section').val() != 'archivi')
  {
    $('#archiveType').parents('.form-group').addClass('hide');
  }

  $('#fields-addRowBtn').on('click',function(){
    var section = $('#section').val();
    var form = $('#form').val();
    var archive = $('#archiveType').val();
    var input = $('input[name="linkedFields"]').last();
    var instance = input.data('instance');
    instance.$element.data('proxy_params','{##section##:##' + section + '##,##form##:##' + form + '##,##archive##:##'+ archive +'##}');
    instance.initialize(instance.$element);
  });
});
