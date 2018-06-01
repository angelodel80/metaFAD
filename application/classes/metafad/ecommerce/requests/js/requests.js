$(document).ready(function(){

  $('.stateLink').on('click',function(){
    if(!$(this).hasClass('stateLinkActive'))
    {
      if(window.confirm('Sicuro di voler cambiare lo stato di questa richiesta?'))
      {
        $('.stateLink').each(function(){
          $(this).removeClass('stateLinkActive');
        });
        $(this).addClass('stateLinkActive');
        updateRequest($(this).children('a').data('value'),'request_state','request_state');
      }
    }
  });

  $('#request_notify').on('change',function(){
    updateRequest($(this).val(),'request_notify','string');
  });

  $('#request_operator').on('change',function(){
    updateRequest($('#request_operator').select2('data'),'request_operator','request_operator');
  });
});

function updateRequest(value,fieldname,type)
{
  var id = $('#__id').val();

  var notify = $('#request_notify').val();
  var email = $('#userEmail').val();

  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.ecommerce.requests.controllers.ajax.UpdateRequest',
    type: 'POST',
    data: {
      id: id,
      value: value,
      fieldname: fieldname,
      type: type,
      notify: notify,
      email: email
    },
    success: function (data, textStatus, jqXHR) {
      console.log('OK');
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore');
    }
  });
}
