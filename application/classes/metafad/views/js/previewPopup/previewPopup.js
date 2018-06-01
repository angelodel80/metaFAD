jQuery(document).ready(function () {
  Glizy.oop.declare("glizy.FormEdit.modalPagePreview", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    pageId: null,
    formtype: null,
    modalDivId: null,
    modalIFrameId: null,
    state: 'edit',

    initialize: function (element) {
      if(window.location.pathname.indexOf('editDraft') > -1)
      {
        this.state = 'editDraft';
      }
      element.data('instance', this);
      this.$element = element;
      this.pageId = element.data('pageid');
      this.formtype = element.data('formtype');
      var controller = element.data('controller');

      this.modalDivId = 'modalDiv-' + element.attr('id');
      this.modalIFrameId = 'modalIFrame-' + element.attr('id');

      var that = this;

      $('.js-glizycms-preview').click(function () {
        console.log(window.location.href);
        that.openModal();
      });
      window.addEventListener("close", this.close, false);
    },

    openModal: function () {
      var w = Math.min($(window).width() - 50, 1100);

      this.$element.addClass('__selectedModalPage');

      Glizy.openIFrameDialog(
        '',
        'index.php?pageId='+this.pageId+'&id='+$('#__id').val()+'&action='+this.state,
        w,
        $(window).width()/4,
        $(window).height()/4,
        this.openDialogCallback
      );

      $('.ui-dialog-titlebar-close').click(function () {
        $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
      });

      $('.ui-dialog-title').html('Anteprima');
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
      var d = event.data;
    },
  });
});
