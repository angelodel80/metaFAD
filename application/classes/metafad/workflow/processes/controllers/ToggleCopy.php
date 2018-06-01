<?php

class metafad_workflow_processes_controllers_ToggleCopy extends metafad_common_controllers_Command
{
    public function execute($id)
    {
        $this->checkPermissionForBackend('edit');
        
// TODO controllo ACL
        if ($id) {
            $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
            $processesProxy = __ObjectFactory::createObject('metafad.workflow.processes.models.proxy.ProcessesProxy');
            $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');

            $instanceData = new stdClass();

            if ($document->load($id)) {
                $data = json_decode($document->getRawData()->document_detail_object);
                if ($arrayInstance = $data->instanceActivities) {
                    foreach ($arrayInstance as $instanceId) {
                        if ($document->load($instanceId)) {
                            $myData = json_decode($document->getRawData()->document_detail_object);
                            foreach ($myData as $key => $value) {
                                $instanceData->$key = $value;
                                if ($key == 'activity') {
                                    $instanceData->$key->id = $value->id;
                                    $instanceData->$key->text = $value->title;
                                }
                            }
                            $instanceActivitiesId[] = $instanceActivitiesProxy->modify(null, $instanceData);
                        } else {
                            return array('http-status' => 400);
                        }
                    }
                }
            } else {
                return array('http-status' => 400);
            }
            $data->instanceActivities = $instanceActivitiesId;
            if ($data->status) {
                unset($data->status);
            }
            if ($data->percentage || $data->percentage == 0) {
                unset($data->percentage);
            }
            if ($data->notes) {
                unset($data->notes);
            }
            $result = $processesProxy->modify(null, $data);

            $updateData = new stdClass();
            $updateData->processId = $result;

            foreach ($instanceActivitiesId as $id) {
                $instanceActivitiesProxy->modify($id, $updateData);
            }

            org_glizy_helpers_Navigation::gotoUrl(org_glizy_helpers_Link::makeUrl('metafad.workflow.processes',
                array('id' => $result, 'action' => 'edit')));
        }
    }
}