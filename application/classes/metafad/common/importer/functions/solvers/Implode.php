<?php

/**
 * Class metafad_common_importer_functions_solvers_Implode
 * Fa la implode dell'array dato
 */
class metafad_common_importer_functions_solvers_Implode implements metafad_common_importer_functions_solvers_SolverInterface
{
    private $separator = "; ";
    private $notAvailable = "";
    private $dictionary = null;

    /**
     * Si aspetta un params stdClass con:
     * separator = stringa che indica se il separatore degli items
     * notAvailable = stringa da usare per indicare la non disponibilità del valore
     * sendToDictionaryId = id del dizionario per inserire i termini (se necessario)
     * @param $params
     */
    function __construct($params){
        $this->separator = $params->separator ?: $this->separator;
        $this->notAvailable = $params->notAvailable ?: $this->notAvailable;
        $this->dictionary = $params->sendToDictionaryId ?: $this->dictionary;
    }

    /**
     * Restituisce un array con un singolo elemento dentro
     * @param array $array
     * @return array
     */
    function solveConflict($array)
    {
        $sender = $this->dictionary ? new metafad_common_importer_functions_transformers_AddToDictionary((object)array("dictionaryId" => $this->dictionary)) : null;
        $array = $sender ? $sender->transformItems($array) : $array;

        $fun = function($a){
            return $a ?: $this->notAvailable;
        };
        $ret = array(implode($this->separator, array_filter(array_map($fun, $array))));

        return $ret;
    }
}