<?php

/**
 * Vedasi POLODEBUG-440
 */
class metafad_common_importer_operations_EADPreSaveCorrections extends metafad_common_importer_operations_LinkedToRunner
{
    function __construct($params, $runner)
    {
        parent::__construct($params, $runner);
    }

    private function extractModelAcronym($data){
        $mod = $data->__model;

        if ($mod == "archivi.models.ComplessoArchivistico"){
            return "ca";
        } else if ($mod == "archivi.models.UnitaArchivistica"){
            return "ua";
        } else if ($mod == "archivi.models.UnitaDocumentaria"){
            return "ud";
        }

        return "";
    }

    private function corrections($data){
        //Punto 3 descrizione POLODEBUG-440
        $data = metafad_common_helpers_ImporterCommons::recursiveHandling($data);
        $data = metafad_common_helpers_ImporterCommons::regenerateConsistence($data, $this->extractModelAcronym($data));

        return $data;
    }

    /**
     * Riceve in input una stdClass con i campi:<br>
     * <ul>
     * <li>data = una stdClass con la scheda da correggere secondo le specifiche POLODEBUG-440</li>
     * </ul>
     * <br>
     * Restituisce in output una stdClass con i campi:<br>
     * <ul>
     * <li>data = la stessa in input corretta secondo POLODEBUG-440</li>
     * </ul>
     * @param stdClass $input
     * @return stdClass
     */
    function execute($input)
    {
        $data = $this->corrections($input->data);

        return (object)array("data" => $data);
    }

    function validateInput($input)
    {
    }
}