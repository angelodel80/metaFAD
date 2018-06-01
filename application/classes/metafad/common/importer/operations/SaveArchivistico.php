<?php

/**
 * Salva l'input nel database usando il proxy dell'archivistico
 */
class metafad_common_importer_operations_SaveArchivistico extends metafad_common_importer_operations_LinkedToRunner
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
        $arcProxy = $this->getOrSetDefault("archiviProxy", __ObjectFactory::createObject("archivi_models_proxy_ArchiviProxy"));
        $arcProxy->setRetryWithDraftOnInvalidate(true)->setQueueSize(500);

        $res = $arcProxy->save($input->data);
        $id = $res['set']['__id'];

        return (object)array("data" => $input->data, "id" => $id);
    }

    function validateInput($input)
    {
    }
}