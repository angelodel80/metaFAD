<?php

class metafad_teca_MAG_controllers_ajax_Validate extends metafad_common_controllers_ajax_CommandAjax
{
    public function execute($data)
    {
        $result = $this->checkPermissionAndInstitute('publish', $data);
        if (is_array($result)) {
            return $result;
        }
        
        if ($data) {
            $c = $this->view->getComponentById('__model');
            $contentProxy = org_glizy_objectFactory::createObject('org.glizycms.contents.models.proxy.ModuleContentProxy');
            $tecaProxy = org_glizy_objectFactory::createObject(str_replace('.Model', '', $c->getAttribute('value')). '.proxy.MagProxy');
            $result = $contentProxy->validate(json_decode($data), $c->getAttribute('value'));
            $this->directOutput = true;
            if ($result === true) {
                $updateInfo = new stdclass();
                $updateInfo->isValid = 1;
                if(json_decode($data)->__id){
                    $tecaProxy->modify(json_decode($data)->__id, $updateInfo);
                }  
                return array('url' => $this->changeAction(''));
            }
            else {
                return array('errors' => $result);
            }
        } 
    }
}