<?php

/**
 * Salva l'input nel database usando il proxy dell'archivistico
 */
class metafad_common_importer_operations_FlushQueue extends metafad_common_importer_operations_LinkedToRunner
{
    private $proxySymbName;

    function __construct($params, $runner)
    {
        parent::__construct($params, $runner);
        $this->proxySymbName = $params ? ($params->proxyName ?: "archiviProxy") : "archiviProxy";
    }

    /**
     * Operazione che permette il flush della coda dell'archiviproxy
     */
    function execute($input)
    {
        /**
         * @var $arcProxy archivi_models_proxy_ArchiviProxy
         */
        $arcProxy = $this->getOrSetDefault($this->proxySymbName, __ObjectFactory::createObject("archivi_models_proxy_ArchiviProxy"));
        $arcProxy->commit();

        return $input;
    }

    function validateInput($input)
    {
    }
}