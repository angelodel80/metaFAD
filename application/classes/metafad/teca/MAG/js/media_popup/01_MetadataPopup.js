jQuery(document).ready(function () {
  Glizy.oop.declare("glizy.FormEdit.metadataPopup", {
    $extends: Glizy.oop.get('glizy.FormEdit.standard'),
    pageId: null,
    formtype: null,
    modalDivId: null,
    modalIFrameId: null,

    initialize: function (element) {
      element.data('instance', this);
      this.$element = element;
      this.pageId = element.data('pageid');
      this.formtype = element.data('formtype');
      var controller = element.data('controller');

      this.modalDivId = 'modalDiv-' + element.attr('id');
      this.modalIFrameId = 'modalIFrame-' + element.attr('id');

      var that = this;

      $('input[name="media-dam"]').on('change',function(){
        that.openModal($(this).val());
      });

      window.addEventListener("metadata", this.receiveMessage, false);
      window.addEventListener("close", this.close, false);
    },

    openModal: function (val) {
      var w = Math.min($(window).width() - 50, 1200);

      this.$element.addClass('__selectedModalPage');

      Glizy.openIFrameDialog(
        '',
        'index.php?pageId='+this.pageId+'&mediaId='+val,
        w,
        $(window).width()/4,
        $(window).height()/4,
        this.openDialogCallback
      );

      $('.ui-dialog-titlebar-close').click(function () {
        $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
      });

      $('.ui-dialog-title').html('Carica metadati da immagine');
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
      if(d['type'] !== 'glizy.message.showSuccess' && d['type'] !== 'glizy.message.showError')
      {
        var metadata = d['metadata'];
        var group = $('#GEN_img_group').find('.GFERowExpanded:visible');
        group.find('select[name="GEN_img_group_image_metrics_samplingfrequencyunit"]').val(metadata['sampling_frequency_unit']);
        group.find('select[name="GEN_img_group_image_metrics_samplingfrequencyplane"]').val(metadata['sampling_frequency_plane']);
        group.find('select[name="GEN_img_group_image_metrics_bitpersample"]').val(metadata['bit_per_sample']);
        group.find('select[name="GEN_img_group_image_metrics_photometricinterpretation"]').val(metadata['photometric_interpretation']);
        group.find('input[name="GEN_img_group_ID"]').val(metadata['groupid']);
        group.find('select[name="GEN_img_group_format_mime"]').val(metadata['mime']);
        group.find('select[name="GEN_img_group_format_compression"]').val(metadata['compression']);
        group.find('input[name="GEN_img_group_scanning_sourcetype"]').val(metadata['source_type']);
        group.find('input[name="GEN_img_group_scanning_scanningagency"]').val(metadata['scanning_agency']);
        group.find('input[name="GEN_img_group_scanning_devicesource"]').val(metadata['device_source']);
        group.find('input[name="GEN_img_group_scanning_scanningsystem_scanner_manufacturer"]').val(metadata['scanner_manufacturer']);
        group.find('input[name="GEN_img_group_scanning_scanningsystem_scanner_model"]').val(metadata['scanner_model']);
        group.find('input[name="GEN_img_group_scanning_scanningsystem_capture_software"]').val(metadata['capture_software']);
        //Fix bug costruzione del JSON
        group.find('input[name="media-dam"]').val("");
      }
    },
  });
});
