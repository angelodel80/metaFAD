$(document).ready(function(){
  var ids = new Array();
  $('#countSelected').html('0 elementi selezionati.');

  $('body').on('change','.selectionflag',function(){
    var checked = $(this).prop('checked');
    var id = $(this).attr('data-id');
    if(checked){
      ids.push(id);
    }
    else{
      var index = ids.indexOf(id);
      ids.splice(index,1);
    }
    $('#countSelected').html(ids.length + ' elementi selezionati.');
    $('#ids').val(ids.join());
  });

  $('#dataGridExport').on('draw.dt',function(){
    $('#dataGridExport').find('input').each(function(){
      if(ids.indexOf($(this).attr('data-id')) > -1)
      {
        $(this).prop('checked',true);
      }
    });
  });

  $('#action').on('click',function(e){
    e.preventDefault();
    $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.gestioneDati.boards.controllers.ajax.Export',
        type: 'POST',
        data: {
            ids: ids,
            exportAll: $('#exportAll').prop('checked'),
            exportSelected: $('#exportSelected').prop('checked'),
            exportTitle: $('#exportTitle').val(),
            exportFormat: $('#exportFormat').val(),
            exportAutBib: $('#exportAutBib').prop('checked'),
            exportEmail: $('#exportEmail').val()
        },
        success: function(data) {
            if(data.url!=null){
              location.href = data.url;
            }else{
              alert(data.msg);
            }
        },
        error: function() {
            alert('Si è verificato un problema.');
        }
    });

  });

});
