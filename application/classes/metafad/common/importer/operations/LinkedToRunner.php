<?php

/**
 * Superclasse di tutte le operazioni che vogliono condividere informazioni con il MainRunner:
 * utile per le operazioni che necessitano di informazioni che non possono ricevere direttamente via pipelining
 */
class metafad_common_importer_operations_LinkedToRunner implements metafad_common_importer_operations_OperationInterface
{
    protected $runner = null;
    protected $idToRunner = null;

    protected function getLastOpKey(){
        return metafad_common_importer_MainRunner::RUNNER_LAST_OPERATION;
    }

    protected function getLastParamsKey(){
        return metafad_common_importer_MainRunner::RUNNER_LAST_PARAMETERS;
    }

    protected function getLastInputKey(){
        return metafad_common_importer_MainRunner::RUNNER_LAST_INPUT;
    }


    /**
     * metafad_common_importer_operations_LinkedToRunner constructor.
     * @param $params stdClass
     * @param $runnerRef metafad_common_importer_MainRunner
     * @param $runnerId
     * @throws Exception
     */
    function __construct($params, $runnerRef, $runnerId = null)
    {
        $this->runner = $runnerRef;
        if (!$this->runner || !is_object($this->runner) || !is_a($this->runner, "metafad_common_importer_MainRunner")){
            throw new Exception("Un'operazione di tipo LinkedToRunner DEVE avere un link ad un MainRunner");
        }
        $this->idToRunner = $runnerId;
    }

    /**
     * @param $key string
     * @param $default mixed
     * @return mixed
     */
    protected function getOrSetDefault($key, $default){
        $ret = $this->runner->get($key);

        if ($ret === null){
            $this->runner->set($key, $default);
            $ret = $default;
        }

        return $ret;
    }

    /**
     * Riporta il progresso di un'esecuzione al mainRunner.
     * @param $completitionRatio mixed numero da 0.0 a 1.0 per il progresso
     * @param $message string messaggio d'accompagnamento
     * @param null $id Id del subtask a cui viene attribuito il completamento. Se è null, viene usato l'id dell'oggetto
     * corrente (se lo ha)
     */
    protected function reportProgress($completitionRatio, $message, $id = null){
        if ($this->idToRunner || ($id !== null && $id !== "")){
            $this->runner->reportProgress($id ?: $this->idToRunner, $completitionRatio, $message);
        }
    }

    function validateInput($input)
    {
        throw new Exception("Operazione non implementata");
    }

    function execute($input)
    {
        throw new Exception("Operazione non implementata");
    }
}