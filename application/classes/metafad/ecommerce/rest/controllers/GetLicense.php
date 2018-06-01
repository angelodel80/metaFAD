<?php
class metafad_ecommerce_rest_controllers_GetLicense extends org_glizy_rest_core_CommandRest
{
  function execute($search)
  {
    $proxy = org_glizy_objectFactory::createObject('metafad.ecommerce.licenses.models.proxy.LicensesProxy');
    $result[] = $proxy->findTerm('','','',$search,'');
    return $result;
  }
}
