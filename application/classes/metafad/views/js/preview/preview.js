jQuery(document).ready(function () {
  $('.GFEButtonContainer').each(function(){
    $(this).addClass('hide');
  });

  $('.GFEEmptyMessage').each(function(){
    $(this).addClass('hide');
  });

  $('.icon.GFERowDelete.GFERightIcon').each(function(){
    $(this).addClass('hide');
  });

  $('input[data-type="mediapicker"]').each(function(){
    $(this).removeAttr('data-type');
  });

  $('input[name="FTA-image"]').remove();

  $('.mediaPickerField').each(function(){
    $(this).after($(this).find('img'));
    $(this).find('img').addClass('hide');
  });

  $('input[data-type="mediapicker"]').on('click',function(e){
    e.stopPropagation();
    e.preventDefault();
  });

  if (window.opener) {
    $('.js-print').addClass('hide');
  }
  $('.js-print').on('click',function(){
    window.open(window.location.href, '_blank');
  });

});
