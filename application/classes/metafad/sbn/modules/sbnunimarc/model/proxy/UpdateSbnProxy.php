<?php

class metafad_sbn_modules_sbnunimarc_model_proxy_UpdateSbnProxy extends GlizyObject
{
    public function updateSbnDigitale($data,$firstImage=null)
    {
      $url = __Config::get('metafad.service.updateSbnDigitale');
      $idpreview = ($firstImage) ? '&idpreview='.$firstImage:'';

      if($data->linkedMedia || $data->linkedInventoryMedia || $data->linkedStruMag || $data->linkedInventoryStrumag || $data->hasFirstImage)
      {
        $digitale = '&digitale=true';
        $digitaleFlag = true;
      }
      else
      {
        $digitale = '&digitale=false';
        $digitaleFlag = false;
      }

      $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'?id='.strtoupper($data->__id).$digitale.$idpreview, 'GET', '', 'application/json');
      $r->execute();
      return $digitaleFlag;
    }

    public function updateSbnVisibility($data,$firstImage=null)
    {
      $url = __Config::get('metafad.service.updateSbnVisibility');
      $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'?id='.strtoupper($data->__id), 'POST', $data->visibility, 'application/json');
      $r->execute();
    }

    //Funzione per update del campo ecommerce per sbn
    public function updateSbnEcommerce($data)
    {
      $url = __Config::get('metafad.service.updateSbnEcommerce');
      $ecommerceHelper = org_glizy_ObjectFactory::createObject('metafad.ecommerce.helpers.EcommerceToSolrHelper');
      $ecommerceInfo = $ecommerceHelper->getEcommerceInfo($data,'sbn');

      $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.'?id='.strtoupper($data->__id), 'POST', $ecommerceInfo, 'application/json');
      $r->execute();
    }
}
