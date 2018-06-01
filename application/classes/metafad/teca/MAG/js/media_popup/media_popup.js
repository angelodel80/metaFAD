jQuery(document).ready(function () {
  Glizy.oop.declare("glizy.FormEdit.modalPageIMG", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    pageId: null,
    formtype: null,
    modalDivId: null,
    modalIFrameId: null,

    initialize: function (element) {
      element.data('instance', this);
      this.$element = element;
      this.pageId = '';
      this.formtype = element.data('formtype');
      var controller = element.data('controller');

      this.modalDivId = 'modalDiv-' + element.attr('id');
      this.modalIFrameId = 'modalIFrame-' + element.attr('id');

      var that = this;

      $('#file').on('click','.singleMediaButton',function () {
        console.log(window.location.href);
        that.pageId = $(this).attr('data-pageid');
        that.openModal($(this).attr('data-id'));
      });
      window.addEventListener("media", this.receiveMessage, false);
      window.addEventListener("close", this.close, false);
    },

    openModal: function (id) {
      var w = Math.min($(window).width() - 50, 1100);

      this.$element.addClass('__selectedModalPage');

      Glizy.openIFrameDialog(
        '',
        'index.php?pageId='+this.pageId+'&action=edit&id='+id,
        w,
        $(window).width()/4,
        $(window).height()/4,
        this.openDialogCallback
      );

      $('.ui-dialog-titlebar-close').click(function () {
        $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
      });

      $('.ui-dialog-title').html('Gestione Media');
      $('.ui-dialog-title').css('font-size','24px');
      $('.ui-dialog-title').css('color','#00c0ef');
      $('.ui-dialog-title').css('float','none');
      $('.ui-dialog-title').parent().css('text-align','center');


    },
    close: function (event)
    {
      $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
    },
    receiveMessage: function (event)
    {
      Glizy.closeIFrameDialog(true);
      var d = event.data
    },
  });

});
