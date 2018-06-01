<?php

/**
 * Class metafad_common_importer_functions_joiners_HorizontalImplode
 * La horizontal implode restituisce un array di implode orizzontale.<br>
 * <p>
 * Esempio:
 * <br>
 * [2, 3, 4],
 * <br>
 * [1, 2],
 * <br>
 * [5, 6, 2],
 * <br>
 * [1, 1, 1, 1]
 * <br>
 * Restituisce
 * <br>
 * [
 * implode(separatore, array(2, 1, 5, 1)),
 * <br>
 * implode(separatore, array(3, 2, 6, 1)),
 * <br>
 * implode(separatore, array(4, N.A., 2, 1)),
 * <br>
 * implode(separatore, array(N.A., N.A., N.A., 1))
 * ]
 * </p>
 */
class metafad_common_importer_functions_joiners_HorizontalImplode implements metafad_common_importer_functions_joiners_JoinerInterface
{
    private $separator = ", ";
    private $notAvailable = "";

    /**
     * Si aspetta un params stdClass con:
     * separator = stringa che indica se il separatore degli items
     * notAvailable = stringa da usare per indicare la non disponibilità del valore
     * @param $params
     */
    function __construct($params){
        $this->separator = $params->separator ?: $this->separator;
        $this->notAvailable = $params->notAvailable ?: $this->notAvailable;
    }

    function joinArrays($array)
    {
        $ret = array();
        $len = max(array_map("count", $array));
        $i = $len;
        while($i-->0){
            $item = array();
            foreach ($array as $list){
                $item[] = key_exists($i, $list) ? $list[$i] : $this->notAvailable;
            }
            $ret[] = implode($this->separator, array_filter($item));
        }

        return $ret;
    }
}