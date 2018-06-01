$(document).ready(function(){
  $('input[name="FNT-unit"]').on('change',function(){
    var archiveData = $(this).select2('data');
    var that = $(this);
    if(archiveData != null)
    {
      $.ajax({
        url: Glizy.ajaxUrl + '&controllerName=metafad.gestioneDati.boards.controllers.ajax.GetArchive',
        type: 'POST',
        data: {
          id: archiveData.id,
          model: archiveData.model
        },
        success: function(data){
          var r = data.result;
          var fnt = that.parents('.GFERowContainer');
          fnt.find('input[name="FNTA"]').val(r.FNTA);
          fnt.find('input[name="FNTT"]').val(r.FNTT);
          fnt.find('input[name="FNTD"]').val(r.FNTD);
          fnt.find('input[name="FNTS"]').val(r.FNTS);
          fnt.find('input[name="FNTI"]').val(r.FNTI);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log('Errore');
        }
      });
    }
  });

  $('input[name="FNTN"]').on('change',function(){
    var id = $(this).select2('val');
    var parent = $(this).parents('.GFERowContainer');
    var instance = parent.find('input[name="FNT-unit"]').data('instance');
    instance.$element.data('proxy_params','{##parentId##:##' + id + '##}');
    instance.initialize(instance.$element);
  });

});
