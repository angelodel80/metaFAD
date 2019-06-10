<?php

class metafad_modules_iccd_models_proxy_IccdFormProxySolr extends GlizyObject
{
  public function findTerm($fieldName, $model, $query, $term, $proxyParams)
  {
    $modules = org_glizy_Modules::getModules();
        //Seleziono il model su cui filtrare la ricerca delle schede collegabili
        //Se proxy params non è settato seleziono tutti i moduli
    if (!$proxyParams->iccdModuleType) {
      $modelsList = array();
      foreach ($modules as $key => $value) {
        if ($value->isICCDModule && !$value->isAuthority) {
          $modelsList[] = $value->model;
        }
      }
    } else {
      foreach ($modules as $key => $value) {
        if ($value->iccdModuleType === $proxyParams->iccdModuleType) {
          $iccdFormModelName = $value->model;
          break;
        }
      }
    }
    $result = array();
    if ($modelsList) {
      $this->getValues($modelsList, $term, $result);
    } 

    return $result;
  }

  public function getValues($modelsList, $term, &$result)
  {
    $instituteKey = metafad_usersAndPermissions_Common::getInstituteKey();
    $queryTerm = 'fq=(instituteKey_s:"'.$instituteKey.'"';
    if($term)
    {
      $queryTerm .= ($term) ? '+AND+uniqueIccdId_s:'. urlencode('*'.$term.'*') : '';
    }
    $queryTerm .= ')';
    
    $models = '(';
    foreach($modelsList as $model)
    {
      $models .= '"'.$model.'" OR ';
    }
    $models = urlencode(rtrim($models , 'OR ') .')');

    $request = org_glizy_objectFactory::createObject(
      'org.glizy.rest.core.RestRequest',
      __Config::get('metafad.solr.url') . 'select?q=document_type_t:' .$models . '&rows=100&wt=json&'. $queryTerm
    );

    $request->setAcceptType('application/json');
    $request->execute();
    
    $response = json_decode($request->getResponseBody())->response;
    if($response->numFound > 0)
    {
      foreach ($response->docs as $doc) {
        if($doc->uniqueIccdId_s)
        {
          $result[] = array(
            'id' => $doc->id,
            'text' => $doc->uniqueIccdId_s
          );
        }
      }
    }
  }
}
