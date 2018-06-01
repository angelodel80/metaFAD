<?php
class metafad_teca_STRUMAG_models_vo_STRUMAGVO
{
    public $id;
    public $MAG;
    public $title;
    public $state;
    public $physicalSTRU;
    public $logicalSTRU;

    public function __construct($ar)
    {
        $instituteKey = $ar->instituteKey ? : '*';
        $dam = __ObjectFactory::createObject('metafad.teca.DAM.services.ImportMedia', $instituteKey);
        $this->id = $ar->getId();
        $this->MAG = $ar->MAG;
        $this->title = $ar->title;
        $this->state = $ar->state;
        $physicalSTRU = json_decode($ar->physicalSTRU);
        $this->physicalSTRU = new stdClass();
        $this->physicalSTRU->image = array();
        foreach($physicalSTRU->image as $image){
            $image->src = $dam->streamUrl($image->id,'original');
            $image->thumbnail = $dam->streamUrl($image->id,'thumbnail');
            $this->physicalSTRU->image[] = $image;
        }
        $this->logicalSTRU = json_decode($ar->logicalSTRU);
    }
}
