<?php
class metafad_ecommerce_helpers_Ecommerce extends GlizyObject
{

  static function countReq()
  {
    $iterator= org_glizy_ObjectFactory::createModelIterator ('metafad.ecommerce.requests.models.Model', 'getCurrentUserRequests');
    return $iterator->count();
  }

}
