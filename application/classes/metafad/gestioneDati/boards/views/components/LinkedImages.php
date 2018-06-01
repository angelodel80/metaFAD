<?php
// TODO esistono 3 LinkedImages uno per modulo fare refactoring per migliorare questa cosa
class metafad_gestioneDati_boards_views_components_LinkedImages extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('model', true, '', COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render()
    {
        $id = __Request::get('id');
        $model = $this->getAttribute('model');
        $imagesDataArray = array();
        
        $ar = org_glizy_objectFactory::createModelIterator($model.'.models.Model')
            ->where("document_id", $id)
            ->orderBy('document_detail_modificationDate', 'DESC')
            ->allStatuses()->first();

        $size = 0;

        if ($ar->FTA) {
            foreach($ar->FTA as $key => $FTA) {
                if ($FTA->{'FTA-image'} && $orig = json_decode($FTA->{'FTA-image'})->id != NULL) {
                    $size++;
                }
            }
        }

        if ($ar->FTA && $size > 0)
        {
          $instituteKey = $ar->instituteKey ? : metafad_usersAndPermissions_Common::getInstituteKey();
          $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);

          $count = 0;
          $output = '<div id="linkedImageContainer" style="display:none"><div id="linkedImages">';
          foreach ($ar->FTA as $key =>$FTA) {
            if (!$FTA->{'FTA-image'}) {
                continue;
            }

            $src = $dam->streamUrl(json_decode($FTA->{'FTA-image'})->id,'thumbnail');

            if($src == null)
            {
              continue;
            }

            $orig = $dam->resizeStreamUrl(json_decode($FTA->{'FTA-image'})->id,'original', __Config::get('gruppometa.dam.maxResizeWidth'));
            $classNavigator = ($size === 1) ? 'hide':'';
            if($count === 0 && $orig != NULL)
            {
              $count++;
              $output .= '<div>
                            <a href="" id="js-image-prev" class="image-navigate '.$classNavigator.'" data-next="'.($size - 1).'">
                              <i class="images-nav fa fa-angle-double-left" aria-hidden="true"></i>
                            </a>
                          </div>
                          <img id="js-linked-img" src="'.$orig.'"/>
                          <div>
                            <div class="commands">
                              <a href="" class="image-close">
                                <i class="commands-action fa fa-times" aria-hidden="true"></i>
                              </a>
                              <a id="js-lightbox-image-a" href="'.$orig.'" class="js-lightbox-image">
                                <i class="commands-action fa fa-eye" aria-hidden="true"></i>
                              </a>
                              <i class="hide commands-action fa fa-search-plus" aria-hidden="true"></i>
                              <i class="hide commands-action fa fa-th" aria-hidden="true"></i>
                                <a href="" id="js-image-next" class="image-navigate '.$classNavigator.'" data-next="1">
                                  <i class="images-nav-right fa fa-angle-double-right" aria-hidden="true"></i>
                                </a>
                            </div>
                          </div>
                          <div id="js-didascalia" class="didascalia">
                          '.$FTA->FTAM.'
                          </div>
                          <div id="image-pagination">
                          1 / '.($size).'
                          </div>';
            }
            $imagesDataArray[] = array("src"=>$src,"didascalia"=>$FTA->FTAM,"orig"=>$orig);
          }

          if ($size == 0 || $count == 0)
          {
            $output = str_replace('<div id="linkedImages">','',$output);
            $output .= '<div class="no-image-message">
            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
            Nessuna immagine collegata</div></div>';
          }
          else {
            $output .= '</div></div>';
          }

          $output .= '<script>
                      var imagesData = new Array(';
          foreach ($imagesDataArray as $value) {
            if($value['src'] != null )
              $output .= 'new Array("'.$value['src'].'","'.$value['didascalia'].'","'.$value['orig'].'"),';
          }
          $output = rtrim($output,",");
          $output .= ');</script>';
        } else {
          $output = '<div id="linkedImageContainer" style="display:none">
                        <div class="no-image-message">
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        Nessuna immagine collegata</div>
                     </div>';
        }
        $this->addOutputCode($output);
    }

    function process()
    {
        $this->_application->addLightboxJsCode();
    }
}
