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

  $('#exportMode').on('change',function(){
    if($(this).val() === 'oai')
    {
      $('#exportFormat').parents('.form-group').hide();
    }
    else {
      $('#exportFormat').parents('.form-group').show();
    }
  });

});
