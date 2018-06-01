<?php

/**
 * Operazione che esegue il blocco di operazioni al suo interno. Inoltra l'input della prima operazione del blocco
 * e restituisce l'output dell'ultima operazione.
 * Utilizzata all'inizio del MainRunner e della Iterate.
 */
class metafad_common_importer_operations_Runner extends metafad_common_importer_operations_LinkedToRunner
{
    protected $ops = array();

    /**
     * metafad_common_importer_operations_Runner constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     */
    public function __construct($params, $runner)
    {
        $this->ops = $params->operations ?: array();
        parent::__construct($params, $runner);
    }

    public function validateInput($input)
    {
        // TODO: Implement validateInput() method.
    }

    public function execute($input)
    {
        $in = $input;
        $estimate = $in && property_exists($in, "estimateProgress");
        $def = metafad_common_importer_MainRunner::RUNNER_DEFAULT_WEIGHT;

        try{
            if ($estimate){
                foreach ($this->ops as $op){
                    $id = mt_rand();
                    $this->runner->addSubtask($id, (@$op->weight ?: $def));
                    $op->runnerId = $id;
                }
            }
            foreach($this->ops as $op){
                $this->runner->set($this->getLastOpKey(), $op->obj);
                $this->runner->set($this->getLastParamsKey(), $op->params);
                $this->runner->set($this->getLastInputKey(), $in);

                /**
                 * @var $operation metafad_common_importer_operations_OperationInterface
                 */
                $operation = __ObjectFactory::createObject($op->obj, $op->params, $this->runner, $op->runnerId);
                $executable = $this->readEnabled($op->enabled);


                if ($executable){
                    if ($op->runnerId){
                        $this->reportProgress(0, end(explode("_", $op->obj)).": iniziata.", $op->runnerId);
                    }
                    $operation->validateInput($in);
                    $in = $operation->execute($in);
                    if ($op->runnerId){
                        $this->reportProgress(1, end(explode("_", $op->obj)).": completata.", $op->runnerId);
                    }
                }

            }
        } catch (Exception $ex){
            throw new Exception(
                "Un'operazione ha generato un'eccezione: {$ex->getMessage()}",
                1,
                $ex
            );
        }

        return $in;
    }

    /**
     * @param $enabled string
     * @return bool
     */
    protected function readEnabled($enabled)
    {
        switch (strtolower($enabled)) {
            case "false":
                $executable = false;
                break;

            case "true":
            case "":
            case null:
                $executable = true;
                break;

            default:
                $executable = __Config::get($enabled);
                break;
        }
        return $executable;
    }
}