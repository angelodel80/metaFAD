<?php

/**
 *
 */
class metafad_common_importer_functions_transformers_StringFromStdClass implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $expression = "";

    /**
     * metafad_common_importer_functions_transformers_ExtractFromNormal constructor.
     * @param $params stdClass Si aspetta una stdClass con il campo "expression" valorizzato
     */
    public function __construct($params)
    {
        @$this->expression = $params->expression;
    }

    /**
     * Usando la expression memorizzata nel costruttore, costruisce una stringa.
     * <br>
     * La expression contiene dei placeholder del tipo ##fieldName##,
     * viene sostituito il placeholder con il valore di fieldName presente nella stdClass passata
     * <br>
     * Eccezione se non è un oggetto
     * @param $stdClass
     * @throws
     * @return string
     */
    private function transformSingle($stdClass){
        if (!is_object($stdClass)){
            throw new Exception("Chiamata StringFromStdClass su una variabile che non contiene una stdClass");
        }

        $built = $this->expression ?: "";
        foreach($stdClass as $k => $v){
            $built = mb_str_replace("##$k##", $stdClass->$k ?: "N.D.", $built);
        }
        $built = preg_replace('/##[^#\s]+##/', '', $built);

        return $built;
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