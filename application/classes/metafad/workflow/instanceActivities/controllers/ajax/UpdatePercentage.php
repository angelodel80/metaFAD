<?php

class metafad_workflow_instanceActivities_controllers_ajax_UpdatePercentage extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data)
    {
        $decodeData = json_decode($data);
        if ($decodeData->instanceActivityId && $decodeData->processId && is_numeric($decodeData->updatePercentageValue)) {
            $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');
            $processProxy = __ObjectFactory::createObject('metafad.workflow.processes.models.proxy.ProcessesProxy');

            $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

            $newData = new stdClass();

            if ($document->load($decodeData->instanceActivityId)) {
                if ($document->getRawData()->percentage) {
                        $oldPercentage = $document->percentage;
                }
            }

            if ($decodeData->updatePercentageValue <= 100 && $decodeData->updatePercentageValue >= 0) {
                $diffPercentage = $decodeData->updatePercentageValue - $oldPercentage;
                
                if($diffPercentage === 0){
                    return array('url' => 'dashboard');
                }

                $newData->percentage = $decodeData->updatePercentageValue;
                if ($newData->percentage == 100) {
                    $newData->status = 2;
                } else if($newData->percentage== 0){
                    $newData->status = 0;
                } else{
                    $newData->status = 1;
                }

                $result = $instanceActivitiesProxy->modify($decodeData->instanceActivityId, $newData);
                $processProxy->updatePercentage($decodeData->processId, $diffPercentage);
            } else {
                return array('errors' => null);
            }
        }
        $this->directOutput = true;
        if ($result) {
            return array('url' => 'dashboard');
        } else {
            return array('errors' => $result);
        }
    }
}