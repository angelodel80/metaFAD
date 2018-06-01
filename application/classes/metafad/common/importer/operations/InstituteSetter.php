<?php

/**
 * Operazione per settare la institute key e l'acronimo di sistema del dato da salvare
 */
class metafad_common_importer_operations_InstituteSetter extends metafad_common_importer_operations_LinkedToRunner
{
    protected $instKey = "";
    protected $instName = "";
    protected $ignoreInput = false;

    /**
     * Riceve un stdClass:<br>
     * <ul>
     * <li>instituteKey = Chiave dell'istituto da linkare</li>
     * <li>instituteName = Nome dell'istituto da linkare (da usare in alternativa alla chiave)</li>
     * <li>ignoreInput = Setta semplicemente in maniera globale l'istituto</li>
     * </ul>
     * metafad_common_importer_operations_InstituteSetter constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runner
     */
    function __construct($params, $runner)
    {
        $this->instKey = $params->instituteKey;
        $this->instName = $params->instituteName;
        $this->ignoreInput = $params->ignoreInput === true;
        parent::__construct($params, $runner);
    }

    /**
     * Riceve in input un stdClass:<br>
     * <ul>
     * <li>data = i dati stdClass da modificare per la chiave di istituto</li>
     * </ul>
     *
     * In output restitisce il solito input con il data aggiornato alla institute key data nei parametri
     * @param stdClass $input
     * @return stdClass
     */
    function execute($input)
    {
        $key = $this->instKey ? $this->instKey : metafad_usersAndPermissions_Common::getInstituteKeyByName($this->instName);
        metafad_usersAndPermissions_Common::setInstituteKey($key);

        if (!$this->ignoreInput){
            $input->data->instituteKey = $key;
        }

        return $input;
    }

    function validateInput($input)
    {
    }
}