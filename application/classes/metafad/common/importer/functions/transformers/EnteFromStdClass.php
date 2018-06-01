<?php

/**
 *
 */
class metafad_common_importer_functions_transformers_EnteFromStdClass implements metafad_common_importer_functions_transformers_TransformerInterface
{
    /**
     * metafad_common_importer_functions_transformers_EnteFromStdClass constructor.
     * @param $params
     */
    public function __construct($params)
    {
    }

    /**
     * @param $stdClass
     * @return mixed
     * @throws Exception
     */
    private function transformSingle($stdClass){
        if (!is_object($stdClass)){
            throw new Exception("Chiamata EnteFromStdClass su una variabile che non contiene una stdClass");
        }

        $data = metafad_common_helpers_ImporterCommons::inferEnteFromIntestazione($stdClass->intestazione);

        foreach($data as $k => $v){
            $stdClass->$k = $v;
        }

        /**
         * @var $proxy archivi_models_proxy_ArchiviProxy
         */
        $proxy = __ObjectFactory::createObject("archivi.models.proxy.ArchiviProxy");
        $ret = $proxy->addOrGetMiniAuthorityLink($stdClass, "archivi.models.Enti", 'externalID', 'intestazione');
        unset($proxy);
        return $ret;
    }

    public function transformItems($array)
    {
        $ret = array();
        $array = array_filter($array, function($a){return $a;});
        foreach ($array as $item){
            $ret[] = $this->transformSingle($item);
        }
        return $ret;
    }
}