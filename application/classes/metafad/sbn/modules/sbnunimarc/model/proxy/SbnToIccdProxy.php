<?php

class metafad_sbn_modules_sbnunimarc_model_proxy_SbnToIccdProxy extends GlizyObject
{
    //models
    //'metafad.sbn.modules.sbnunimarc.model.Model'
    //'metafad.sbn.modules.authoritySBN.model.Model'
    public function updateSbnToIccd($field,$value,$id,$model)
    {
      $sbn = org_glizy_objectFactory::createModelIterator($model)
            ->where($field,$id)->first();
      if($sbn)
      {
        $sbn->linkedIccd = (string)$value;
        $sbn->save();
      }
    }

    public function updateSbnToIccdSolr($bid, $iccd, $type)
    {
      $url = ($type == 'sbn') ? __Config::get('metafad.service.updateSbnToIccd') : __Config::get('metafad.service.updateSbnToIccdAut') ;
      $body = '?id='.$bid;
      if($iccd)
      {
        $body .= '&linkedIccd='.$iccd;
      }
      $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.$body, 'GET', '', 'application/json');
      $r->execute();
    }

    public function deleteSbnToIccdSolr($iccd, $type, $model)
    {
      $it = org_glizy_objectFactory::createModelIterator($model)
            ->where('linkedIccd',$iccd)->first();
      $id = $it->id;
      $url = ($type == 'sbn') ? __Config::get('metafad.service.updateSbnToIccd') : __Config::get('metafad.service.updateSbnToIccdAut') ;
      $body = '?id='.$id;
      $r = org_glizy_ObjectFactory::createObject('org.glizy.rest.core.RestRequest', $url.$body, 'GET', '', 'application/json');
      $r->execute();
    }
}
