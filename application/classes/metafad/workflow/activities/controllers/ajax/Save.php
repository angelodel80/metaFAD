<?php

class metafad_workflow_activities_controllers_ajax_Save extends org_glizycms_contents_controllers_moduleEdit_ajax_Save
{
    public function execute($data)
    {
        $it = org_glizy_objectFactory::createModelIterator('metafad.workflow.instanceActivities.models.Model');
        $instanceActivitiesProxy = __ObjectFactory::createObject('metafad.workflow.instanceActivities.models.proxy.InstanceActivitiesProxy');

        $myData = new stdClass();
        $decodeData = json_decode($data);

        $activity = new stdClass();
        $activity->id = $decodeData->__id;
        $activity->text = $decodeData->title;
        $myData->activity = $activity;

        foreach ($it as $ar) {
            $activityId = $ar->getRawData()->activity->id;
            if($activityId == $decodeData->__id){
                $instanceActivitiesProxy->modify($ar->document_id, $myData);
            }
        }

        return parent::execute($data);
    }
}