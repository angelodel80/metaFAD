<?php

class metafad_workflow_activities_controllers_Delete extends org_glizycms_contents_controllers_moduleEdit_Delete
{
    public function execute($id, $model)
    {
// TODO controllo ACL
        $processesProxy = __ObjectFactory::createObject('metafad.workflow.processes.models.proxy.ProcessesProxy');

        if ($processesProxy->instanceActivityControll($id)) {
            $this->logAndMessage( "Errore cancellazione attività \n (non è possibile cancellare un'attività collegata ad un processo)", '', GLZ_LOG_ERROR);
            org_glizy_helpers_Navigation::goHere();
        } else {
            parent::execute($id, $model);
        }

    }
}