<?php

/**
 * Class metafad_common_importer_functions_transformers_PurgeVoids
 * Classe che filtra l'array con gli elementi che hanno valorizzato almeno uno dei campi elencati nei parametri
 */
class metafad_common_importer_functions_transformers_PurgeVoids implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $fieldList = array();

    /**
     * metafad_common_importer_functions_transformers_ExtractMagRef constructor.
     * Si aspetta un params stdClass con:
     * fields = array di stringhe che contiene il nome dei campi da controllare (vuota equivale a filtrare su tutti i campi
     * a runtime)
     * @param $params
     */
    function __construct($params){
        $this->fieldList = is_array($params->fields) ? $params->fields : $this->fieldList;
    }

    function transformItems($array)
    {
        $ret = array();

        foreach ($array as $item){
            $fields = $this->fieldList ?: array_keys((array)$item);
            $len = count($fields);
            $found = false || $len == 0 || (!is_object($item) && !is_array($item));

            $i = 0;
            while($i < $len && !$found){
                $found = $found || @$item->{$fields[$i++]};
            }

            if ($found){
                $ret[] = $item;
            }
        }

        return $ret;
    }
}