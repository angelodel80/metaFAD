<?php

/**
 *
 */
class metafad_common_importer_functions_transformers_IntestazioneFromStdClass implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $tipo = "";

    /**
     * metafad_common_importer_functions_transformers_IntestazioneFromStdClass constructor.
     * @param $params stdClass Si aspetta una stdClass con il campo "tipo" valorizzato
     */
    public function __construct($params)
    {
        @$this->tipo = $params->tipo;
    }

    /**
     * @param $stdClass
     * @return null|string
     * @throws Exception
     */
    private function transformSingle($stdClass){
        if (!is_object($stdClass)){
            throw new Exception("Chiamata IntestazioneFromStdClass su una variabile che non contiene una stdClass");
        }

        return metafad_common_helpers_ImporterCommons::getIntestazione($stdClass, $this->tipo);
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