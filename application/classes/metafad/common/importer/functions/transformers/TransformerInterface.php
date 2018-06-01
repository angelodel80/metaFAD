<?php

/**
 * Interface metafad_common_importer_functions_transformers_TransformerInterface
 * Serve per tradurre un array di elementi in un array di elementi trasformati (è una banalissima map)
 */
interface metafad_common_importer_functions_transformers_TransformerInterface
{
    /**
     * @param $array array
     * @return mixed
     */
    function transformItems($array);
}