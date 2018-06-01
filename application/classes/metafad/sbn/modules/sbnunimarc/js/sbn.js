$(document).ready(function () {

    setTimeout(function () {
        $('#relatedBoardGrid_wrapper').hide();
        $('#kardexGrid_wrapper').hide();

        $("#relatedBoardGrid").removeAttr('style');
        $("#kardexGrid").removeAttr('style');

        $(".OpenGrid").removeAttr('href');

        $(".OpenGrid").click(function () {
            //Chiamata ajax a controller per aggiornare contenuti KardexGrid
            var kardexUrl = $(this).data('info');
            $.ajax({
              url: Glizy.ajaxUrl + '&controllerName=metafad.sbn.modules.sbnunimarc.controllers.ajax.RefreshKardexGrid',
              type: 'POST',
              data: {
                kardexUrl : kardexUrl
              },
              complete: function (data, textStatus, jqXHR) {
                  
                $(".OpenGrid.relatedBoardLink").fadeOut("fast", function () {
                    $(".OpenGrid.relatedBoardLink").hide();
                });
                
                $("#relatedBoardGrid_wrapper").fadeIn("fast", function () {
                    $("#relatedBoardGrid_wrapper").show();
                });
                    
                $('#kardexGrid_wrapper').html(data.responseText);
                $("#kardexGrid_wrapper").show();
                $('#kardexGrid_wrapper').get(0).scrollIntoView(); 
              }
            });

            $("#kardexGrid_wrapper").on("click", ".CloseGrid" ,function () {
                $("#kardexGrid_wrapper").hide();

                $(".OpenGrid.relatedBoardLink").fadeIn("fast", function () {
                    $(".OpenGrid.relatedBoardLink").show();
                });
                $("#relatedBoardGrid_wrapper").fadeOut("fast", function () {
                    $("#relatedBoardGrid_wrapper").hide();
                });
            });
        });
    }, 0);

    Glizy.oop.declare("glizy.FormEdit.modalPage", {
        $extends: Glizy.oop.get('glizy.FormEdit.standard'),
        pageId: null,
        modalDivId: null,
        modalIFrameId: null,

        initialize: function (element) {
            element.data('instance', this);
            this.$element = element;
            this.pageId = element.data('pageid');
            var controller = element.data('controller');

            this.modalDivId = 'modalDiv-' + element.attr('id');
            this.modalIFrameId = 'modalIFrame-' + element.attr('id');

            var that = this;

            $('.unimarc a').click(function () {
                href = $(this).data("url");
                that.openModal(href);
            });
        },

        openModal: function (href) {
            var w = Math.min($(window).width() - 50, 1200);

            this.$element.addClass('__selectedModalPage');

            Glizy.openIFrameDialog(
                    '',
                    href,
                    w,
                    50,
                    50,
                    this.openDialogCallback
            );

            $('.ui-dialog-titlebar-close').click(function () {
                $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
            });
        }
    });
  
    $('.rif').click(function () {
        var w = Math.min($(window).width() - 50, 1200);
        Glizy.openIFrameDialog(
                '',
                $(this).data('url'),
                w,
                50,
                50,
                this.openDialogCallback
        );
    })

    $('input.ResynchKardex').click(function() {
        var that = this;
        
        $(this).attr('disabled', 'disabled');
        
        if (confirm('I media già associati verranno persi continuare?')) {
            var url = $(this).data('url');
    
            $.ajax({
                url: Glizy.ajaxUrl + '&controllerName=metafad.sbn.modules.sbnunimarc.controllers.ajax.ResynchKardex',
                type: 'POST',
                data: {
                    url : url
                },
                complete: function (data, textStatus, jqXHR) {
                    alert('Aggiornamento completato');
                    $(that).prop('disabled', false);
                }
            });
        } else {
            $(that).prop('disabled', false);
        }
    });
});

function iframeLoaded(url) {
    $('#modalIFrame').attr('src', url);
}
