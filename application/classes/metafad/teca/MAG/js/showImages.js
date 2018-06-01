jQuery(document).ready(function () {
	if($('.js-lightbox-image').length > 0)
	{
		$("a.js-lightbox-image").colorbox({rel:'group1',transition:"none", width:"95%", height:"95%", slideshow:true, slideshowAuto:false});
		$('.link.showImagesMAG').removeAttr("href");
	}
	$('.link.showImagesMAG').on('click',function(){
		$("#linkedImageContainer").toggle(400);
	})

	$('#panelImages').on('click','.image-navigate',function(e){
		//0 - thumbnail
		//1 - didascalia
		//2 - original
		e.stopPropagation();
		e.preventDefault();
		nextImageIndex = parseInt($(this).attr('data-next'));
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
				$('#js-image-prev').attr('data-next',nextImageIndex-1);
			}
			else {
				$('#js-image-prev').attr('data-next',imagesData.length - 1);
			}
			$('#js-lightbox-image-a').attr('href',imagesData[nextImageIndex][2]);
			if(nextImageIndex+1 in imagesData){
				$('#js-image-next').attr('data-next',nextImageIndex+1);
			}
			else {
				$('#js-image-next').attr('data-next',0);
			}
			$('#image-pagination').html(nextImageIndex+1 + ' / ' +imagesData.length);
		}
	});

	$('#panelImages').on('click','.image-close',function(e){
		e.stopPropagation();
		e.preventDefault();
		$("#linkedImageContainer").toggle(400);
	});
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
