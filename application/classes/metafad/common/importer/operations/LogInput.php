<?php

/**
 * Logging dell'output
 */
class metafad_common_importer_operations_LogInput extends metafad_common_importer_operations_LinkedToRunner
{
    /**
     * @var stdClass
     */
    protected $instructions = null;
    /**
     * @var org_glizy_log_LogBase
     */
    protected $logger = null;

    /**
     * $params avrà i seguenti campi:
     * <br>
     * - instructions, una stdClass contenente vari campi di istruzione per il logging (dopo saranno specificati)
     * <br>
     * - logger, una org_glizy_log_LogBase per il logging dei messaggi di cui sopra
     * <br>
     * <br>
     * - struttura di instructions:
     * <ol>
     *  <li>
     *      group (facoltativo): il gruppo del logger (org_glizy_log_LogBase::debug() secondo argomento)
     *  </li>
     *  <li>
     *      valueSrc (facoltativo): una stdClass chiave->valore con chiave il nome da attribuire ad un certo valore.
     *      Tale valore sarà, invece, una stringa che rappresenta dove andare a trovare il valore all'interno dell'input.
     *      Siccome la struttura della stdClass input è annidata, la sintassi della stringa è <i>field1[->field1+n]*</i>.
     *      I valori saranno converiti con una print_r.
     *  </li>
     *  <li>
     *      message (facoltativo): una stringa che contiene il messaggio da mostrare. È possibile creare dei placeholder
     *      il cui valore sarà rimpiazzato da ciò che viene preso da valueSrc. La sintassi del placeholder è "<##nome##>"
     *  </li>
     * </ol>
     * metafad_common_importer_operations_LogInput constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     */
    function __construct($params, $runner)
    {
        @$this->instructions = $params->instructions ?: null;
        @$this->logger = $params->logger ?: null;
        parent::__construct($params, $runner);
    }

    private function generateOutputFromInstructions($input){
        $toPrint = null;
        $input = json_decode(json_encode($input), false);
        $struct = array();

        if (!$this->instructions){
            $toPrint = print_r($input,true);
        } else {
            if (@$this->instructions->valueSrc){
                foreach ($this->instructions->valueSrc as $k => $v){
                    $pivot = $input;
                    $fields = explode("->", $v);
                    for($i = 0; $i < count($fields); $i++){
                        $field = $fields[$i];
                        if ($pivot && isset($pivot->$field)){
                            $pivot = $pivot->$field;
                        } else {
                            $pivot = null;
                        }
                    }

                    if ($pivot !== null){
                        @$struct[$k] = print_r($pivot, true);
                    }
                }
            }

            if ((@$this->instructions->message) && !empty($struct)){
                $msg = $this->instructions->message;
                foreach ($struct as $k => $v){
                    $msg = str_replace("<##$k##>", $v, $msg);
                }
                $toPrint = $msg;
            } else if (!empty($struct)) {
                $toPrint = print_r($struct, true);
            } else {
                $toPrint = print_r($input, true);
            }
        }

        return $toPrint;
    }

    function execute($input)
    {
        $toPrint = $this->generateOutputFromInstructions($input);

        if (!$this->logger){
            echo $toPrint."\n<br>\n";
        } else {
            $group = @$this->instructions->group;
            $this->logger->debug($toPrint, $group ?: "");
        }
        return $input;
    }

    function validateInput($input)
    {
    }
}