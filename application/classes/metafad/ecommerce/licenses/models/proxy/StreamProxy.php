<?php
class metafad_ecommerce_licenses_models_proxy_StreamProxy extends GlizyObject
{
    public function findTerm($fieldName, $model, $query, $term, $proxyParams)
    {
        $damService = __ObjectFactory::createObject('metafad_teca_DAM_services_DamService');
        $streamTypes = $damService->getAllStreamTypes();

        $result = array();

        foreach($streamTypes as $i => $value) {
            $result[] = array(
              'id' => $i,
              'text' => $value
            );
        }

        return $result;
    }
}
