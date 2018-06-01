<?php

/**
 * Interface metafad_common_importer_functions_joiners_JoinerInterface
 * Serve a fare la join di array di array per restituirne uno singolo.
 */
interface metafad_common_importer_functions_joiners_JoinerInterface
{
    /**
     * @param $arrays array
     * @return mixed
     */
    function joinArrays($arrays);
}