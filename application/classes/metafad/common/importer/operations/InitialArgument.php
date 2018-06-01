<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 22/11/16
 * Time: 15.34
 */
class metafad_common_importer_operations_InitialArgument extends metafad_common_importer_operations_LinkedToRunner
{
    protected $args = null;

    /**
     * metafad_common_importer_operations_InitialArgument constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     * @throws Exception
     */
    function __construct($params, $runner)
    {
        if (gettype($params) != "stdClass"){
            throw new Exception("Non si può costruire InitialArgument con un argomento che non sia un stdClass.");
        }
        $this->args = $params;
        parent::__construct($params, $runner);
    }

    function execute($input)
    {
        return $this->args;
    }

    function validateInput($input)
    {
        return "";
    }
}