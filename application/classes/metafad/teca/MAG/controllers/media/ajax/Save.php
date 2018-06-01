<?php

class metafad_teca_MAG_controllers_media_ajax_Save extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data)
    {
      $conditionHelper = org_glizy_objectFactory::createObject('metafad_teca_MAG_helpers_ConditionHelper');
      $decodeData = json_decode($data);
      $type = lcfirst(end(explode(".",$decodeData->__model)));
      $errors = $conditionHelper->checkMediaCondition($decodeData,$type);
      if(empty($errors))
      {
        return parent::execute($data);
      }
      else {
        $this->directOutput = true;
        return array('errors' => $errors);
      }
    }
}
