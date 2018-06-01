<?php

/**
 * Class metafad_common_importer_operations_MergeObject
 * Fa il merge dell'oggetto passato con l'oggetto dato in parametro, la sovrascrittura di eventuali campi comuni
 * viene evitata se specificato esplicitamente con params->overwrite === false
 */
class metafad_common_importer_operations_MergeObject extends metafad_common_importer_operations_LinkedToRunner
{
    protected $object = null;
    protected $overwrite = true;

    /**
     * metafad_common_importer_operations_InitialArgument constructor.
     * Si aspetta $params->object che sarà una stdClass
     * Si aspetta $params->overwrite che sarà un booleano (default = true)
     *
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     * @throws Exception
     */
    function __construct($params, $runner)
    {
        if (gettype($params->object) != "object"){
            throw new Exception("Non si può costruire MergeObject con un argomento che non sia un stdClass.");
        }
        $this->object = $params->object;
        $this->overwrite = $params->overwrite !== false;
        parent::__construct($params, $runner);
    }

    /**
     * Si aspetta in input uno stdClass con i campi:
     * data = stdClass da fare merge con la stdClass passata via costruzione
     *
     * @param stdClass $input
     * @return stdClass stesso input, ma con il data modificato
     */
    function execute($input)
    {
        foreach($this->object as $k => $v){
            $input->data->$k = ($this->overwrite) ? $v : ($input->data->$k ?: $v);
        }

        return $input;
    }

    function validateInput($input)
    {
        if (!is_a($input->data, "stdClass")){
            throw new Exception("Argomento passato in input al MergeObject non è una stdClass con un campo \"data\" stdClass a sua volta");
        }
    }
}