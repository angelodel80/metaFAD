<?php
class metafad_teca_MAG_views_components_LinkedImages extends org_glizy_components_Component
{
  function init()
  {
    // define the custom attributes
    parent::init();
  }

  function render()
  {
    $id = __Request::get('id');
    $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
    $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);

    if($id)
    {
      $docStruProxy = $this->_application->retrieveService('metafad.teca.MAG.models.proxy.DocStruProxy');
      $root = $docStruProxy->getRootNodeByDocumentId($id);

      $idRoot = $root->docstru_id;
      $imagesDataArray = array();
      //
      $ar = org_glizy_objectFactory::createModelIterator('metafad.teca.MAG.models.Img')
      ->where('docstru_rootId', $idRoot)
      ->where('docstru_type','img')->first();

      $img = org_glizy_objectFactory::createModelIterator('metafad.teca.MAG.models.Img')
      ->where('docstru_rootId', $idRoot)
      ->where('docstru_type','img');
      $size = $img->count();

      $prev = '<div id="linkedImageContainer" style="display:none"><div id="linkedImages">';
      $output = '';

      $thumbnail = $dam->streamUrl($ar->dam_media_id,'thumbnail');
      $orig = $dam->streamUrl($ar->dam_media_id,'original');
      $nomenclature = $ar->nomenclature;
      $classNavigator = ($count > 0) ? 'hide':'';

      $output .= '<div>
      <a href="" id="js-image-prev" target="_blank" class="image-navigate '.$classNavigator.'" data-next="'.($size - 1).'">
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
      '.$nomenclature.'
      </div>
      <div id="image-pagination">
      1 / '.($size).'
      </div>';

      foreach ($img as $i) {
        $t = $dam->streamUrl($i->dam_media_id,'thumbnail');
        $o = $dam->streamUrl($i->dam_media_id,'original');
        $imagesDataArray[] = array("src"=>$t,"didascalia"=>$i->nomenclature,"orig"=>$o);
      }


      if($size > 0)
      {
        $output = $prev . $output;
        $output .= '</div></div>';
      }
      else
      {
        $output = '<div id="linkedImageContainer" style="display:none">
        <div class="no-image-message">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        Nessuna immagine collegata</div>
        </div>';
      }

      $output .= '<script>
      var imagesData = new Array(';
      foreach ($imagesDataArray as $value) {
        $output .= 'new Array("'.$value['src'].'","'.$value['didascalia'].'","'.$value['orig'].'"),';
      }
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
