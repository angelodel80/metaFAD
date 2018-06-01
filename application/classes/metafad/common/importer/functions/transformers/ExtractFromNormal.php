<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 16/01/17
 * Time: 17.08
 */
class metafad_common_importer_functions_transformers_ExtractFromNormal implements metafad_common_importer_functions_transformers_TransformerInterface
{
    private $type = "";
    private $splitter = "-";

    /**
     * metafad_common_importer_functions_transformers_ExtractFromNormal constructor.
     * @param $params stdClass Si aspetta una stdClass con il campo "fieldType" valorizzato
     */
    public function __construct($params)
    {
        @$this->type = $params->fieldType;
        @$this->splitter = $params->splitter ?: "-";
    }

    private function transformSingle($string){
        $strings = explode($this->splitter, $string);
        $remoto = count($strings) > 0 ? array(
            substr($strings[0], 0, 4),
            substr($strings[0], 4, 2),
            substr($strings[0], 6, 2)
        ) : array(null, null, null);
        $recente = count($strings) > 1 ? array(
            substr($strings[1], 0, 4),
            substr($strings[1], 4, 2),
            substr($strings[1], 6, 2)
        ) : array(null, null, null);

        switch (strtoupper($this->type)){
            case "ESTREMO_TESTUALE":
                return metafad_common_helpers_ImporterCommons::getCronologicoTestuale(
                    metafad_common_helpers_ImporterCommons::formatDateYMD($remoto[0], $remoto[1], $remoto[2]),
                    metafad_common_helpers_ImporterCommons::formatDateYMD($recente[0], $recente[1], $recente[2])
                );
            case "REMOTO_DATA":
                return metafad_common_helpers_ImporterCommons::formatDateYMD($remoto[0], $remoto[1], $remoto[2]);
            case "REMOTO_CODIFICA":
                return $strings[0] ?: "";
            case "RECENTE_DATA":
                return metafad_common_helpers_ImporterCommons::formatDateYMD($recente[0], $recente[1], $recente[2]);
            case "RECENTE_CODIFICA":
                return $strings[1] ?: "";
            default:
                return "";
        }

    }

    public function transformItems($array)
    {
        $ret = array();
        foreach (array_filter($array, function($a){return $a;}) as $item){
            $ret[] = $this->transformSingle($item);
        }
        return $ret;
    }
}