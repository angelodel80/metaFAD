jQuery(document).ready(function () {
  $('input[name="stream"]').on('click',function(){
    var val = $(this).val();
    $('.stream-container').addClass('hide');
    $('div[data-stream="'+val+'"]').removeClass('hide');
    $('.js-stream-name').html(val);
  });

  var message = new Event('metadata');

  $('.js-import').on('click',function(){
    var data = new Array();
    var arrayCount = new Array();
    $('input:checked').each(function(){
      data = {'metadata':metadataArray[$(this).val()]};
    });
    message.data = data;
    window.top.dispatchEvent(message);
  });

  $('.js-back').on('click',function(){
    window.history.back();
  });
});
