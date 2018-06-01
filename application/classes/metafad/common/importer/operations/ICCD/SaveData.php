<?php

class metafad_common_importer_operations_ICCD_SaveData extends metafad_common_importer_operations_LinkedToRunner
{
    protected
        $iccdProxy;

    /**
     * Si aspetta:
     * metafad_common_importer_operations_TRCToStdClass constructor.
     * @param stdClass|null $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    public function __construct($params, metafad_common_importer_MainRunner $runnerRef)
    {
        parent::__construct($params, $runnerRef);

        $this->iccdProxy = $this->getOrSetDefault("iccdProxy", __ObjectFactory::createObject('metafad.gestioneDati.boards.models.proxy.ICCDProxy'));
    }

    /**
     * Riceve:
     * data = dati in formato finale (precedente al salvataggio)
     *
     * Output:
     * result = array associativo con i risultati del salvataggio
     * data = dati in formato finale (precedente al salvataggio)
     *
     * @param stdClass $input
     * @return stdClass
     * @throws Exception se il file non esiste
     */
    public function execute($input)
    {
        $data = $input->data;

        $result = $this->iccdProxy->save($data, true);

        return (object)array("result" => $result, "data" => $data);
    }
    public function validateInput($input)
    {
        // TODO: Change the autogenerated stub
    }

}