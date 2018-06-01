<?php

class metafad_workflow_processes_controllers_ajax_AddNote extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data)
    {
        $decodeData = json_decode($data);
        if($decodeData->instanceActivityId && $decodeData->newNoteValue){
            $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');
            $newNote = new stdClass();
            $newNote->detail = $decodeData->newNoteValue;
            $newNote->created_at = date("d/m/Y");
            $newNote->created_by = "Mario Rossi";
            $result = $instanceActivitiesProxy->addNote($decodeData->instanceActivityId, $newNote);
        }
        $this->directOutput = true;
        if ($result) {
            if(isset($decodeData->dashboard)){
                return array('url' => 'dashboard');
            }
            else{
                return array('url' => 'processi-definizione-processi/detail/' . $decodeData->processId . '/');
            }
        } else {
            return array('errors' => $result);
        }
    }
}