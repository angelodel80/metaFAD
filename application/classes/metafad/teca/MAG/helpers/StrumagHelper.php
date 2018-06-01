<?php
class metafad_teca_MAG_helpers_StrumagHelper extends GlizyObject
{
  private $elementChecked = array();

  public function setElementChecked($stru)
  {
    if($stru)
    {
      foreach ($stru as $key => $value) {
        $this->elementChecked[] = $key;
      }
    }
  }

  public function createTree($element,$level)
  {
    $output = '';
    foreach ($element as $e) {
      $folder = $e->folder;
      $faclass = ($folder == true) ? 'fa-folder-o' : 'fa-align-left';
      $checked = (in_array($e->key,$this->elementChecked)) ? 'checked="checked"' : '';
      $input = '<input data-key="'.$e->key.'" type="checkbox" '.$checked.'/>';
      if($e->key == 'exclude')
      {
        $input = '';
      }
      if($folder)
      {
        $output .= '<li data-key="'.$e->key.'" class="js-stru js-showElements">
        <div>'.$input.'<i class="fa fa-caret-right carets js-caret"></i>
        <i class="fa '.$faclass.'" aria-hidden="true"></i>
        '.$e->title.'</div>';
      }
      else
      {
        $output .= '<li data-key="'.$e->key.'" class="element-clickable js-showElements">
        <div>'.$input.'<i class="fa '.$faclass.'" aria-hidden="true"></i>
        '.$e->title.'</div>
        </li>';
      }

      $children = $e->children;
      if($children)
      {
        $output .= '<ul class="">';
        $output .= $this->createTree($children,$level+1);
        $output .= '</ul>';
      }

      if($folder)
      {
        $output .= '</li>';
      }
    }
    return $output;
  }

  public function createShowElement($idStru)
  {
    $output .=  '<div class="actionBar actionsStru">
                    <div class="actionStru js-actionStru" data-type="saveAll" data-stru="'.$idStru.'">
                      <i class="fa fa-asterisk" aria-hidden="true"></i> Importa
                    </div>
                    <div class="actionStru js-actionStru" data-type="deleteAll" data-stru="'.$idStru.'">
                      <i class="fa fa-times" aria-hidden="true"></i> Scollega
                    </div>
                    <div class="actionStru js-actionStru" data-type="saveChecked" data-stru="'.$idStru.'">
                      <i class="fa fa-check-square-o" aria-hidden="true"></i> Collega nodi selezionati
                    </div>
                    <div class="actionStru js-actionStru" data-type="saveCheckedMedia" data-stru="'.$idStru.'">
                      <i class="fa fa-file-image-o" aria-hidden="true"></i></i> Collega media selezionati
                    </div>
                  </div>
                  <ul id="fileTabsStru" class="nav nav-tabs hide">
                    <li id="tab-image" data-type="IMAGE" class="active nav-tabs-elements"><a href data-target="#img" data-toggle="tab" aria-expanded="true">Immagini</a></li>
                    <li id="tab-doc" data-type="PDF" class="nav-tabs-elements"><a href data-target="#doc" data-toggle="tab" aria-expanded="true">Documenti</a></li>
                    <li id="tab-audio" data-type="AUDIO" class="nav-tabs-elements"><a href data-target="#audio" data-toggle="tab" aria-expanded="true">Audio</a></li>
                    <li id="tab-video" data-type="VIDEO" class="nav-tabs-elements"><a href data-target="#video" data-toggle="tab" aria-expanded="true">Video</a></li>
                    <li id="tab-ocr" class="nav-tabs-elements"><a href data-target="#ocr" data-toggle="tab" aria-expanded="true">OCR</a></li>
                    <li id="tab-dis" class="nav-tabs-elements"><a href data-target="#dis" data-toggle="tab" aria-expanded="true">DIS</a></li>
                  </ul>
                  <div id="elementContent">
                    Nessun elemento selezionato
                  </div>';
    return $output;
  }

  public function getElementsStru($physicalStru)
  {
    $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
    $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);

    if($physicalStru)
    {
      foreach ($physicalStru as $mediaType){
        foreach ($mediaType as $media) {
          $thumbnail = $dam->streamUrl($media->id,'thumbnail');
          $keyNode = $media->keyNode;
          $src = $dam->streamUrl($media->id,'original');
          if($media->type == 'IMAGE')
          {
            if($keyNode !== null)
            {
              $elements .= '<img data-element="'.$keyNode.'" data-mediaid="'.$media->id.'" class="element-media hide" data-type="IMAGE" src="'.$thumbnail.'"/>';
            }
          }
          else
          {
            if($keyNode !== null)
            {
              $elements .= '<img data-element="'.$keyNode.'" data-mediaid="'.$media->id.'" class="element-media hide" data-type="'.$media->type.'" src="'.$thumbnail.'"/>';
            }
          }

          if(!empty($media->aliasKeyNode))
          {
            foreach($media->aliasKeyNode as $kn)
            {
              $elements .= '<img data-element="' . $kn . '" data-mediaid="' . $media->id . '" class="element-media hide" data-type="IMAGE" src="' . $thumbnail . '"/>';
            }
          }
        }
      }
    }

    return $elements;
  }
}
