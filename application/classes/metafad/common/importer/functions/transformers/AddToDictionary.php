<?php

/**
 * Aggiunge un valore nel dizionario prescelto e poi lo restituisce (ignora i fallimenti)
 */
class metafad_common_importer_functions_transformers_AddToDictionary implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $dictionaryId = "";
    private $ignoreFailures = true;

    /**
     * metafad_common_importer_functions_transformers_ExtractFromNormal constructor.
     * @param $params stdClass Si aspetta una stdClass con il campo "dictionaryId" valorizzato
     */
    public function __construct($params)
    {
        @$this->dictionaryId = $params->dictionaryId;
        @$this->ignoreFailures = $params->ignoreFailures !== false || $params->ignoreFailures !== "false";
    }

    /**
     * Aggiunge il valore nel dizionario se è "stringabile" e lo restituisce
     * @param $string
     * @throws
     * @return string
     */
    private function transformSingle($string){
        if (is_object($string) || is_array($string)){
            throw new Exception("Chiamata AddToDictionary su una variabile che non si converte in stringa");
        }
        $ret = $string;

        try{
            $ret = metafad_common_helpers_ImporterCommons::addOrGetTerm($this->dictionaryId, $string) ?: "";
        } catch (Exception $ex) {
            if (!$this->ignoreFailures){
                throw $ex;
            }
        }

        return $ret;
    }

    public function transformItems($array)
    {
        $ret = array();

        foreach ($array as $item){
            $ret[] = $this->transformSingle($item);
        }

        return $ret;
    }
}