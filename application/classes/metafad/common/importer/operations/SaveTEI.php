<?php

/**
 * Salva l'input nel database usando il proxy dell'archivistico
 */
class metafad_common_importer_operations_SaveTEI extends metafad_common_importer_operations_LinkedToRunner
{
    function __construct($params, $runner)
    {
        parent::__construct($params, $runner);
    }

    /**
     * Riceve in input una stdClass con i campi:<br>
     * <ul>
     * <li>data = una stdClass da passare all'archiviProxy</li>
     * </ul>
     * <br>
     * Restituisce in output una stdClass con i campi:<br>
     * <ul>
     * <li>data = la stessa in input</li>
     * <li>id = identificatore restituito dal salvataggio</li>
     * </ul>
     * @param stdClass $input
     * @return stdClass
     */
    function execute($input)
    {
        /**
         * @var $arcProxy archivi_models_proxy_ArchiviProxy
         */
        $teiProxy = $this->getOrSetDefault("TEIProxy", __ObjectFactory::createObject("metafad.tei.models.proxy.ModuleProxy"));
        //$arcProxy->setQueueSize(500);

        $res = $teiProxy->save($input->data);
        //$id = $res['set']['__id'];

        return (object)array("data" => $input->data, "id" => $res);
    }

    function validateInput($input)
    {
    }
}
