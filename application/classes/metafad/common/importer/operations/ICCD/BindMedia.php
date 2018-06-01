<?php

/**
 * Created by PhpStorm.
 * User: marco
 * Date: 28/11/16
 * Time: 12.17
 */
class metafad_common_importer_operations_ICCD_BindMedia extends metafad_common_importer_operations_LinkedToRunner
{
    protected
        $immftan;

    /**
     * Si aspetta:
     * imagesDir
     * immftanClass
     * immftanArg
     * metafad_common_importer_operations_TRCToStdClass constructor.
     * @param stdClass $params
     * @param metafad_common_importer_MainRunner $runnerRef
     */
    public function __construct(stdClass $params, metafad_common_importer_MainRunner $runnerRef)
    {
        parent::__construct($params, $runnerRef);

        $this->immftan = $this->getOrSetDefault("immftan", __ObjectFactory::createObject($params->immftanClass, $params->immftanArg));
    }

    /**
     * Riceve:
     * data = dati in formato finale (precedente al salvataggio)
     *
     * @param stdClass $input
     * @return stdClass come l'input, ma con data modificato
     * @throws Exception se il file non esiste
     */
    public function execute($input)
    {
        $data = $input->data;

        if ($this->immftan != NULL) {
            $this->setImages($data);
        }

        return $input;
    }

    public function validateInput($input)
    {
        // TODO: Change the autogenerated stub
    }


    private function setImages(&$data)
    {
        $images = $this->immftan->getImages($data);
        if (is_array($images)){
            foreach ($images as $i => $image) {
                if ($image != null) {
                    $data->FTA[$i]->{'FTA-image'} = __ObjectFactory::createObject("metafad_common_importer_utilities_ImagePlaceholder", $image);
                }
            }
        }
    }
}