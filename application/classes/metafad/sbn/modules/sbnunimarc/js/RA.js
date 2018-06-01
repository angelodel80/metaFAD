$(document).ready(function(){
  $('.advancedSearch').on('change','select[name="searchKey"]',function(){
    if($(this).val() == 'digitale_s')
    {
      var input = $(this).parents().siblings('.col-sm-5').children('input[name="searchValue"]');
      input.addClass('hide');
      input.parents('.col-sm-5').prepend('<input name="searchValue" type="checkbox" value="1" class="digitalCheck"/>');
    }
    else
    {
      var input = $(this).parents().siblings('.col-sm-5').children('input[name="searchValue"]');
      input.addClass('hide');
      input.parents('.col-sm-5').prepend('<input type="text" name="searchValue" class="searchValue form-control style="display:inline-block;"/>');
    }
  })

  $('.advancedSearch').on('change','.digitalCheck',function(){
    if(this.checked){
      $(this).siblings('input').val('1');
    }
    else {
      $(this).siblings('input').val('');
    }
  });
});
