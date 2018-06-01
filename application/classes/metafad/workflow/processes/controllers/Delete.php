<?php

class metafad_workflow_processes_controllers_Delete extends org_glizycms_contents_controllers_moduleEdit_Delete
{
    public function execute($id, $model)
    {
        $document = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
        $document2 = org_glizy_objectFactory::createObject('org.glizy.dataAccessDoctrine.ActiveRecordDocument');
        
        if ($document->load($id)) {
            if ($document->getRawData()->instanceActivities) {
                foreach ($document->instanceActivities as $instanceActivity) {
                    $document2->delete($instanceActivity);
                }
            }
        } else {
            return array('http-status' => 400);
        }
        parent::execute($id, $model);
    }
}