<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 22/11/16
 * Time: 10.31
 */
class metafad_common_importer_MainRunner extends GlizyObject
{
    /**
     * @var $job metacms_jobmanager_service_JobService
     */
    protected $job = null;
    protected $blackboard = array();
    protected $subtasks = array();

    const RUNNER_LAST_OPERATION = "Last_Read_Runner_Operation";
    const RUNNER_LAST_PARAMETERS = "Last_Read_Runner_Operation_Parameters";
    const RUNNER_LAST_INPUT = "Last_Read_Runner_Operation_Input";
    const RUNNER_DEFAULT_WEIGHT = 100;

    /**
     * @param $id
     * @param $weight
     * @param float $completitionRatio
     */
    public function addSubtask($id, $weight, $completitionRatio = 0.0){
        $this->subtasks[$id] = array(
            "weight" => (int) $weight,
            "progress" => (float) $completitionRatio
        );
    }

    /**
     * Lancia un evento "mainRunnerProgress" con:
     * <br>
     * <ul>
     *   <li>"progress" in [0.0, 100.0]</li>
     *   <li>"message" stringa di messaggio</li>
     * </ul>
     * <br>
     * Inoltre, aggiorna lo stato di completamento del subtask indicato
     * @param $id mixed id del subtask che riporta un progresso
     * @param $completitionRatio float numero in [0.0, 1.0] che rappresenta lo stato di completamento
     * @param $message string messaggio da riportare
     */
    public function reportProgress($id, $completitionRatio, $message){
        $this->subtasks[$id]['progress'] = $completitionRatio;

        $tot = 0;
        $cur = 0;
        foreach ($this->subtasks as $id => $task){
            $tot += $task['weight'];
            $cur += $task['weight'] * $task['progress'];
        }
        $progress = $tot != 0 ? ($cur * 100 / $tot) : 100;

        $evt = array("type" => "mainRunnerProgress", "data" => array("progress" => $progress, "message" => $message));
        $this->dispatchEvent($evt);
    }

    /**
     * @return metacms_jobmanager_service_JobService
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param metacms_jobmanager_service_JobService $job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    public function get($key)
    {
        return $this->blackboard[$key];
    }

    public function set($key, $value)
    {
        $lastValue = $this->blackboard[$key];
        $this->blackboard[$key] = $value;
        return $lastValue;
    }

    public function executeFromJson($filename)
    {
        return $this->executeFromJsonString(file_get_contents($filename));
    }

    public function executeFromJsonString($json)
    {
        $ops = json_decode($json);
        if ($ops === null) {
            throw new Exception("Il JSON delle operazioni non è stato scritto correttamente in JSON: " . json_last_error_msg());
        }
        return $this->executeFromStdClasses($ops);
    }

    public function executeFromStdClasses($ops)
    {
        $begin = new stdClass();
        $begin->operations = $ops;

        //EstimateProgress è una proprietà per l'avvio del reporting del progress
        $in = new stdClass();
        $in->estimateProgress = true;
        /**
         * @var metafad_common_importer_operations_Runner $mainRunner
         */
        $mainRunner = __ObjectFactory::createObject("metafad_common_importer_operations_Runner", $begin, $this);
        try{
            $ret = $mainRunner->execute($in);
        } catch (Exception $ex){
            throw new Exception(
                $this->getMessage(),
                1,
                $ex);
        }
        return $ret;
    }

    /**
     * @return string
     */
    private function getMessage()
    {
        return "Il Runner ha lanciato eccezione" . "\r\n\r\n" .
        "- Ultima operazione prima dell'eccezione: " . $this->get(self::RUNNER_LAST_OPERATION) . "\r\n\r\n" .
        "- Parametri dell'operazione: " . print_r($this->get(self::RUNNER_LAST_PARAMETERS), true) . "\r\n\r\n" .
        "- Input dell'operazione: " . print_r($this->get(self::RUNNER_LAST_INPUT), true);
    }
}