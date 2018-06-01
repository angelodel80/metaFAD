<?php
class metafad_viewer_helpers_ViewerHelper extends GlizyObject
{
  public function getKey($check)
  {
    return ($check)?:'*';
  }

  public function initializeDam($key){
    if($key) {
      return __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $key);
    }
    else {
      return __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', '*');
    }
  }
}
