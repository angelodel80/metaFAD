<?php
// TODO esistono 3 LinkedImages uno per modulo fare refactoring per migliorare questa cosa
class metafad_sbn_modules_sbnunimarc_views_components_LinkedImages extends org_glizy_components_Component
{
    function init()
    {
        // define the custom attributes
        $this->defineAttribute('model', true, '', COMPONENT_TYPE_STRING);
        parent::init();
    }

    function render()
    {
        $images = array();
        $id = __Request::get('id');
        $model = $this->getAttribute('model');
        $imagesDataArray = array();
        $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
        $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);

        $ar = org_glizy_objectFactory::createModelIterator($model)
              ->where("id", $id)
              ->orderBy('document_detail_modificationDate', 'DESC')
              ->allStatuses()->first();

        if($ar->linkedStruMag)
        {
          $stru = org_glizy_objectFactory::createModel('metafad.teca.STRUMAG.models.Model');
          $struMagId = is_array($ar->linkedStruMag) ? $ar->linkedStruMag['id'] : $ar->linkedStruMag->id;
          $stru->load($struMagId);
          if($stru->physicalSTRU){
            foreach(json_decode($stru->physicalSTRU)->image as $image)
            {
              $images[] = $image;
              $size++;
            }
          }
        }
        if($ar->linkedInventoryStrumag)
        {
          foreach($ar->linkedInventoryStrumag as $image)
          {
            $stru = org_glizy_objectFactory::createModel('metafad.teca.STRUMAG.models.Model');
            $stru->load($image->linkedStruMagToInventory->id);
            if($stru->physicalSTRU)
            {
              $media = json_decode($stru->physicalSTRU)->image;
              foreach ($media as $m) {
                $images[] = $m;
                $size++;
              }
            }
          }
        }
        if($ar->linkedMedia)
        {
          foreach ($ar->linkedMedia as $image) {
            $images[] = json_decode($image->media);
            $size++;
          }
        }
        if($ar->linkedInventoryMedia)
        {
          foreach ($ar->linkedInventoryMedia as $image) {
            $media = $image->media;
            foreach ($media as $m) {
              $images[] = json_decode($m->mediaInventory);
              $size++;
            }
          }
        }
        if(!empty($images))
        {
          $count = 0;
          $output = '<div id="linkedImageContainer" style="display:none"><div id="linkedImages">';
          foreach ($images as $image) {
            $src = $dam->streamUrl($image->id,'thumbnail');

            if($src == null)
            {
              continue;
            }

            $orig = $dam->streamUrl($image->id,'original');
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
                           '.$image->label.'
                          </div>
                          <div id="image-pagination">
                          1 / '.($size).'
                          </div>';
            }
            $imagesDataArray[] = array("src"=>$src,"didascalia"=>$image->label,"orig"=>$orig);
          }
          if($size == 0 || $count == 0)
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
          //var_dump($output); exit;
          $output = rtrim($output,",");
          $output .= ');</script>';
        }
        else
        {
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
