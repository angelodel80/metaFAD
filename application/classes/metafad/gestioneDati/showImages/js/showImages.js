var rebootLinkedImages = function () {
  var linkShowImages = $('.link.showImages');
  var aJsLightboxImage = $("a.js-lightbox-image");
  var imageClose = $('.image-close');
  var imageNavigate = $('.image-navigate');

  linkShowImages.unbind("click");
  imageNavigate.unbind("click");
  imageClose.unbind("click");

  aJsLightboxImage.colorbox({transition:"none", width:"95%", height:"95%", slideshow:false, slideshowAuto:false});
  linkShowImages.removeAttr("href");

  linkShowImages.on('click',function(){
    $("#linkedImageContainer").toggle(400);
  });
  imageNavigate.on('click',function(e){
    //0 - thumbnail
    //1 - didascalia
    //2 - original
    e.stopPropagation();
    e.preventDefault();
    nextImageIndex = ($(this).data('next'));
    if(nextImageIndex >= 0)
    {
      $('#js-linked-img').attr('src',imagesData[nextImageIndex][0]);
      if(imagesData[nextImageIndex][1] == ''){
        var didascalia = '<span class="no-didascalia">Nessuna didascalia</span>';
      }
      else {
        var didascalia = imagesData[nextImageIndex][1];
      }
      $('#js-didascalia').html(didascalia);
      if(nextImageIndex-1 in imagesData){
        $('#js-image-prev').data('next',nextImageIndex-1);
      }
      else {
        $('#js-image-prev').data('next',imagesData.length - 1);
      }
      $('#js-lightbox-image-a').attr('href',imagesData[nextImageIndex][2]);
      if(nextImageIndex+1 in imagesData){
        $('#js-image-next').data('next',nextImageIndex+1);
      }
      else {
        $('#js-image-next').data('next',0);
      }
      $('#image-pagination').html(nextImageIndex+1 + ' / ' +imagesData.length);
    }
  });

  imageClose.on('click',function(e){
    e.stopPropagation();
    e.preventDefault();
    $("#linkedImageContainer").toggle(400);
  });
};
jQuery(document).ready(rebootLinkedImages);

function refreshComponentById(glizyComponentId, htmlDestNodeId, recordId, action, stripScripts)
{
  stripScripts = typeof stripScripts !== 'undefined' ? stripScripts : true;
  $.ajax({
    url: Glizy.ajaxUrl + '&controllerName=metafad.common.controllers.ajax.RerenderComponent',
    type: 'POST',
    data: {
      "sendOutput": glizyComponentId,
      "sendOutputState": action,
      "sendOutputFormat": "html",
      "id": recordId
    },
    success: function (data, textStatus, jqXHR) {
      var $data = $(data);

      if (stripScripts) { //Aggiunge un nodo script, ma non verrà eseguito se lo aggiungo e basta.
        var script = $data.find("script");
        eval(script.html());
        script = $data.filter("script");
        eval(script.html());
        script.remove();
      }

      var dataPlainHtml = $data.clone().wrap('<p>').parent().html();
      $('#'+htmlDestNodeId).replaceWith(dataPlainHtml);
      rebootLinkedImages();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.log('Errore: ' + textStatus + ", " + JSON.stringify(errorThrown));
      console.log(JSON.stringify(jqXHR));
    }
  });
}

//POLODEBUG-464
$(document).ajaxComplete(function (event, xhr, settings) {
  var action = settings.data;
  var thisUrl = window.location.pathname;

  var possibleActions = [
    "edit",
    "editDraft"
  ];

  if (action === undefined) {
    action = settings.action;
  }

  if (action !== undefined) {
    if (action.indexOf("action=saveDraft&") == 0 || action.indexOf("action=save&") == 0 || action.indexOf("action=validate&") == 0) {
      var response = xhr.responseJSON;
      var savedId = ((response || {}).set || {}).__id;

      var act = action.indexOf("action=saveDraft&") == 0 ? "editDraft" : "edit";

      if (action.indexOf('metafad.sbn.modules.sbnunimarc.model.Model') != -1) {
          savedId = response.set.bid;
          act = 'show';
      }

      if (savedId){
        refreshComponentById("linkedImages", "linkedImageContainer", savedId, act);
      }
    }
  }
});

//POLODEBUG-125
$(document).ready(function(){
	$('#cboxNext').addClass('display-buttons');
	$('#cboxPrevious').addClass('display-buttons');

	$('#cboxNext').on('click',function(){
		$('#js-image-next').trigger('click');
		$('#js-lightbox-image-a').trigger('click');
	});
	$('#cboxPrevious').on('click',function(){
		$('#js-image-prev').trigger('click');
		$('#js-lightbox-image-a').trigger('click');
	});
});
