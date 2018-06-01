<?php

/**
 * Class metafad_common_importer_functions_solvers_Implode
 * Fa la implode dell'array dato
 */
class metafad_common_importer_functions_solvers_ToStringImplode implements metafad_common_importer_functions_solvers_SolverInterface
{
    private $separator = ";; ";
    private $internalSeparator = ", ";
    private $showKeys = false;

    /**
     * Si aspetta un params stdClass con:
     * separator = stringa che indica se il separatore degli items
     * internalSeparator = stringa che indica il separatore tra i campi di un item
     * showKeys = booleano che fa la stringify pure della chiave
     * @param $params
     */
    function __construct($params){
        $this->separator = ($params && $params->separator) ? $params->separator : $this->separator;
        $this->internalSeparator = ($params && $params->internalSeparator) ? $params->internalSeparator: $this->internalSeparator;
        $this->showKeys = $params && $params->showKeys;
    }

    /**
     * Restituisce un array con un singolo elemento dentro
     * @param array $array
     * @return array
     */
    function solveConflict($array)
    {
        $ret = array(implode($this->separator, array_filter(array_map(function($a){return $this->stringify($a);}, $array))));

        return $ret;
    }

    function stringify($obj){
        $arr = array();

        if (is_object($obj) || is_array($obj)){
            foreach($obj as $k => $v){
                if ($v){
                    $arr[] = ($this->showKeys ? "$k: " : ""). "$v";
                }
            }
        }

        return implode($this->internalSeparator, $arr);
    }
}