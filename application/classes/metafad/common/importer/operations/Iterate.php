<?php

/**
 * Classe che Itera su un insieme di argomenti, viene costruito con il blocco di operazioni da eseguire e
 * esegue tale blocco per ogni argomento ricevuto in input. Restituisce l'output dell'ultima operazione eseguita.
 */
class metafad_common_importer_operations_Iterate extends metafad_common_importer_operations_LinkedToRunner
{
    protected $ops = array();
//    protected $pipeOverIteration = false;

    /**
     * metafad_common_importer_operations_Iterate constructor.<br>
     * Ammesso uno stdClass con i seguenti campi:<br>
     * <ul>
     * <li>operations: contiene un array di metafad_common_importer_operations_OperationInterface</li>
     * </ul>
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     */
    function __construct($params, $runner)
    {
        $this->ops = $params->operations ?: array();
//        $this->pipeOverIteration = $params->pipeiterate ? true : false;
        parent::__construct($params, $runner);
    }

    /**
     * Itera le operazioni passate dal costruttore sull'insieme di argomenti passato.<br>
     * Ammesso uno stdClass con i seguenti campi:<br>
     * <ul>
     * <li>argset: contiene un array di stdClass che saranno i valori dell'argomento iniziale del blocco di iterazione</li>
     * </ul>
     * @param stdClass $input
     * @return stdClass Restituito uno stdClass analogo a quello di prima, con i valori trasformati alla fine.
     */
    function execute($input)
    {
        // TODO: Implement execute() method.
        $argomento = new stdClass();
        $argomento->operations = $this->ops;
        /**
         * @var metafad_common_importer_operations_Runner $runner
         */
        $runner = __ObjectFactory::createObject("metafad.common.importer.operations.Runner", $argomento, $this->runner);

        $args = $input->argset ?: array();
        $out = new stdClass();
        $out->argset = array();

        foreach ($args as $k => $arg){
            $out->argset[] = $runner->execute($arg);
        }

        return $out;
    }

    /**
     * @param stdClass $input
     * @throws Exception
     */
    function validateInput($input)
    {
        if (!is_array($input->argset)){
            throw new Exception("L'input passato all'argset non è un array ma: " . gettype($input->argset));
        }
    }
}