jQuery(document).ready(function () {
  $('.link.SBN').removeAttr("href");

  Glizy.oop.declare("glizy.FormEdit.modalPageSBN", {
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

      $('.link.SBN').click(function () {
        console.log(window.location.href);
        that.openModal();
      });
      window.addEventListener("sbn", this.receiveMessage, false);
      window.addEventListener("close", this.close, false);
    },

    openModal: function () {
      var w = Math.min($(window).width() - 50, 1200);

      this.$element.addClass('__selectedModalPage');

      Glizy.openIFrameDialog(
        '',
        'index.php?pageId='+this.pageId+'&formtype='+this.formtype,
        w,
        $(window).width()/2,
        $(window).height()/4,
        this.openDialogCallback
      );

      $('.ui-dialog-titlebar-close').click(function () {
        $('.ui-dialog.ui-widget.ui-widget-content.ui-corner-all.ui-front.ui-draggable').remove();
      });

      $('.ui-dialog-title').html('Carica dati da SBN WEB');
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
      var bid = event.bid;
      if(d['type'] !== 'glizy.message.showSuccess' && d['type'] !== 'glizy.message.showError')
      {
        $('input#BID').val(bid);
        for (id in d)
        {
          var value = d[id]['valore'];
          var parent = d[id]['parent'];
          var type = d[id]['type'];
          var n = d[id]['n'];

          if(type === 'AUT' && value !== undefined)
          {
            var importVID = confirm('Stai importando un autore con VID: '+value+'\n Il sistema ne verificherà l\'esistenza in authority ed effettuerà i legami nel paragrafo AU');
          }
          else
          {
            var importVID = false;
          }

          var inputThesaurus = 'undefined-'+id;
          var button = parentUntil(d,id) + '-addRowBtn';

          if($('input#'+type+'-addRowBtn').length && importVID === false)
          {
            $('input#'+type+'-addRowBtn').click();
          }
          else if($('input#'+button).length)
          {
            $('input#'+button).click();
            $('#s2id_'+type).children('a').children('span').html(value);
          }
          else if ($('fieldset[id=' + type + ']').length && ($('fieldset[id=' + type + ']').data('type') == 'repeat' || $('fieldset[id=' + type + ']').data('type') == 'FormEditRepeatMandatory'))
          {
            var element = $('fieldset[id='+type+']');
            element.find('input[type=button]').click();
          }

          if($('label[for='+type+']').next().find('a').children('span').length)
          {
            var parentDiv = 'undefined-'+parent;
            $('#'+parentDiv).find('label[for='+type+']').next().find('a').children('span').html(value);
          }

          //Se sto importando un VID si può fare su richiesta dell'utente
          //una query al servizio per i dati AU
          if(type === 'AUT' && value !== undefined && importVID)
          {
            var vid = value;
            $.ajax({
              url: Glizy.ajaxUrl + '&controllerName=metafad.gestioneDati.sbnweb.controllers.ajax.GetAuthor',
              type: 'POST',
              data: {
                formModel: $('#__model').val(),
                vid: value,
                n: n
              },
              error: function (jqXHR, textStatus, errorThrown) {
                console.log('Errore');
              },
              complete: function(data){
                var AUTvalues = data.responseJSON.result.values;
                if(AUTvalues)
                {
                  var AUTn = data.responseJSON.result.n;
                  $('input#AUT-addRowBtn').click();
                  for(var key in AUTvalues)
                  {
                    if($('input[name='+key+']').length)
                    {
                      var element = $('input[name='+key+']:eq('+AUTn+')');
                      if(element.length == 0)
                      {
                        for(i = parseInt(AUTn);i >= 0; i--)
                        {
                          var element = $('input[name='+key+']:eq('+i+')');
                          if(element.length != 0)
                          {
                            break;
                          }
                        }
                      }

                      if(element.data('proxy') !== undefined && key != '__AUT')
                      {
                        checkVocabulary(element,AUTvalues[key],'AUT',2);
                      }
                      else
                      {
                        if(key == '__AUT')
                        {
                          element.select2('data', {id: AUTvalues[key]['id'], text: AUTvalues[key]['text']});
                          element.val(AUTvalues[key]['id']);
                          element.siblings('div').children('a').children('span').html(AUTvalues[key]['text']);
                        }
                        else {
                          element.val(AUTvalues[key]);
                          element.siblings('div').children('a').children('span').html(AUTvalues[key]);
                        }
                      }
                    }
                  }
                }
                else
                {
                    window.alert('Attenzione: l\'autore selezionato (VID: '+vid+') non è presente nel sistema.\nNecessario importarlo da SBN su apposita scheda AUT');
                }
              }
            });
          }
          else if($('input#'+type).length)
          {
            var element = $('input#'+type);
            if(element.data('proxy') !== undefined)
            {
              checkVocabulary(element,value,type,1);
            }
            else
            {
              element.val(value);
              $('#s2id_'+type).children('a').children('span').html(value);
            }
          }
          else if($('textarea#'+type).length)
          {
            var element = $('textarea#'+type);
            element.val(value);
          }
          else if($('textarea[name="'+type+'"]').length)
          {
            var element = $('textarea[name="'+type+'"]');
            element.val(value);
          }
          else if($('input[name='+type+']').length)
          {
            var element = $('input[name='+type+']:eq('+n+')');
            if(element.data('proxy') !== undefined)
            {
              checkVocabulary(element,value,type,2);
            }
            else
            {
              element.val(value);
              element.siblings('div').children('a').children('span').html(value);
            }
          }
          else if($('input[name='+type+'-element]').length)
          {
            var element = $('input[name='+type+'-element]:eq('+n+')');
            if(element.data('proxy') !== undefined)
            {
              checkVocabulary(element,value,type,2);
            }
            else
            {
              element.val(value);
              element.siblings('div').children('a').children('span').html(value);
            }
          }
        }
      }
    },
  });

  //Funzione per verificare se il termine che si sta aggiungendo da SBNWEB
  //è presente o meno nel vocabolario chiuso del campo, in modo da evitare
  //errori
  function checkVocabulary(element,value,type,inputType)
  {
    //Seleziono name o field, dando precedenza a field (caso particolare)
    if(element.data('field'))
    {
      var field = element.data('field');
    }
    else
    {
      var field = element.attr('name');
    }
    //Alcuni campi arrivano vuoti (in realtà servono solo a premere i pulsanti dei repeater)
    //ma non hanno dei valori da inserire, quindi non ha senso proseguire nella funzione
    if(value === undefined)
    {
      return;
    }
    //Se è vocabolario chiuso devo controllare se c'è già il termine prima di aggiungerlo
    //Se non c'è, non devo aggiungere il valore nel form
    //Se è aperto controllo se c'è il termine, se non c'è lo devo aggiungere al dizionario

    //Selezione del controller da utilizzare
    if(element.data('add_new_values') == false)
    {
      var controllerName = '&controllerName=metafad.modules.thesaurus.controllers.ajax.CheckVC';
    }
    else
    {
      var controllerName = '&controllerName=metafad.modules.thesaurus.controllers.ajax.CheckVA';
    }
    var proxyParams = element.data('proxy_params');
    proxyParams = proxyParams.replace(/##/g, "\"");
    $.ajax({
      url: Glizy.ajaxUrl + controllerName,
      type: 'POST',
      data: {
        value: value,
        field: field,
        proxyParams: proxyParams
      },
      success: function (data, textStatus, jqXHR) {
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log('Errore');
      },
      complete: function(data){
        if(data.responseJSON.result == 'add')
        {
          element.val(value);
          if(inputType === 1)
          {
            $('#s2id_'+type).children('a').children('span').html(value);
          }
          else
          {
            element.siblings('div').children('a').children('span').html(value);
          }
        }
      }
    });
  }

  function parentUntil(d,id)
  {
    var parent = undefined;
    var parentString = d[id]['type'];
    while (parent != ''){
      p = d[id]['parent'];
      parent = p;
      id = p;
      parentString = p + '-' + parentString;
    }
    return 'undefined'+parentString;
  }
});
