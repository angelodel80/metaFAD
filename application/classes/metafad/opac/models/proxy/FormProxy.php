<?php

class metafad_opac_models_proxy_FormProxy extends GlizyObject
{
  public function findTerm($fieldName, $model, $query, $term, $proxyParams)
  {
    $modules = org_glizy_Modules::getModules();
    $modelsList = array();
    foreach ($modules as $key => $value) {
      if($value->isICCDModule && !$value->isAuthority)
      {
        $modelsList[] = array(
          'id' => $value->iccdModuleType,
          'text' => $value->classPath,
        );
      }
    }
    return $modelsList;
  }
}
