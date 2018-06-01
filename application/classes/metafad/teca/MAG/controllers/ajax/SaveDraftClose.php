<?php
class metafad_teca_MAG_controllers_ajax_SaveDraftClose extends metafad_teca_MAG_controllers_ajax_SaveDraft
{
    public function execute($data)
    {
        $decodeData = json_decode($data);
        $decodeData->isValid = 0;
        if($decodeData->linkedStru)
        {
          $decodeData->linkedStru = array("id"=>$decodeData->linkedStru->id,"text"=>$decodeData->linkedStru->text);
        }
        $data = json_encode($decodeData);
        $result = parent::execute($data);

        return array('url' => $this->changeAction(''));
    }

}
