<?php

class metafad_workflow_processes_controllers_ToggleStart extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');
        
        if ($id) {
            $processesProxy = __ObjectFactory::createObject('metafad.workflow.processes.models.proxy.ProcessesProxy');
            $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');

            $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');

            $data = new stdClass();
            $data->status = 1;
            $data->processStatus = 1;

            if($document->load($id)) {
                if ($document->getRawData()->instanceActivities) {
                    foreach ($document->instanceActivities as $instanceId) {
                        $instanceActivitiesProxy->modify($instanceId, $data);
                    }
                }
            }

            $data->percentage = 0;

            $processesProxy->modify($id, $data);

            org_glizy_helpers_Navigation::goHere();
        }
    }
}