<?php

/**
 * Logging dell'output
 */
class metafad_common_importer_operations_ICCD_CountSchede extends metafad_common_importer_operations_LinkedToRunner
{
    protected $num = 0;

    function __construct($params, $runner)
    {
        parent::__construct($params, $runner);
    }

    function execute($input)
    {
        echo "Schede inserite: ".count($input->argset)."\n<br>\n";
        return $input;
    }

    function validateInput($input)
    {
    }
}
