<?php

/**
 * Interface metafad_common_importer_functions_solvers_SolverInterface
 * Serve per restituire un array contenente un singolo valore
 */
interface metafad_common_importer_functions_solvers_SolverInterface
{
    /**
     * @param $array array
     * @return mixed
     */
    function solveConflict($array);
}