<?php

/**
 * Interfaccia base delle operazioni: ha i metodi di esecuzione e validazione dell'input
 */
interface metafad_common_importer_operations_OperationInterface
{
    /**
     * Esegue un'operazione, prende in input un stdClass e restituisce un stdClass in output
     * @param $input stdClass
     * @return stdClass
     */
    public function execute($input);

    /**
     * Fa la validazione dell'input, lancia eccezione se l'input non è valido
     * @param $input stdClass
     * @throws Exception
     */
    public function validateInput($input);
}