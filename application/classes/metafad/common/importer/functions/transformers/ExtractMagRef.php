<?php

/**
 * Class metafad_common_importer_functions_transformers_ExtractMagRef
 * Estrae il riferimento al mag dalla stringa data in input
 */
class metafad_common_importer_functions_transformers_ExtractMagRef implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $romanConv = false;

    /**
     * metafad_common_importer_functions_transformers_ExtractMagRef constructor.
     * Si aspetta un params stdClass con:
     * romanConv = booleano che indica se convertire il primo pezzo in numeri romani
     * @param $params
     */
    function __construct($params){
        $this->romanConv = $params->romanConv === true;
    }

    function transformItems($array)
    {
        $arr = array();
        if ($this->romanConv){
            $arr = array_map(function($a){return $this->getRomanVersion(trim($a), ".");}, $array);
        } else {
            $arr = array_map("trim", $array);
        }

        return array_map(function($a){return $a ? __ObjectFactory::createObject("metafad_common_importer_utilities_MAGPlaceholder" , $a) : null;}, $arr);
    }

    function getRomanVersion($string, $separator){
        $arr = explode($separator, $string);
        $first = array_shift($arr);

        if (is_numeric($first)){
            $first = metafad_common_helpers_RomanService::integerToRomanic($first);
        }

        return implode($separator, array_merge(array($first), $arr));
    }
}